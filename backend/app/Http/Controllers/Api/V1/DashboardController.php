<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    /** Statuses that represent realised revenue (the order has been paid). */
    private const REVENUE_STATUSES = [
        Order::STATUS_PAID,
        Order::STATUS_ACCEPTED,
        Order::STATUS_IN_PROGRESS,
        Order::STATUS_READY,
        Order::STATUS_COMPLETED,
    ];

    /**
     * Role-aware dashboard metrics. Admins see platform-wide figures; store
     * owners/staff see only the stores they belong to (via the store_user pivot).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = (bool) $user->is_admin;
        $storeIds = $isAdmin ? null : $user->stores()->pluck('stores.id')->all();

        // Base query: scoped to the user's stores (or all for admins), drafts excluded.
        $scoped = fn (): Builder => Order::query()
            ->when($storeIds !== null, fn (Builder $q) => $q->whereIn('store_id', $storeIds))
            ->where('status', '!=', Order::STATUS_DRAFT);

        // ── Time series: last 14 days (built in PHP so it's DB-agnostic) ──
        $since = Carbon::today()->subDays(13);
        $recent = $scoped()->where('placed_at', '>=', $since)->get(['placed_at', 'total_cents', 'status']);
        $byDay = $recent->groupBy(fn (Order $o) => optional($o->placed_at)->toDateString());
        $days = collect(range(0, 13))->map(fn (int $i) => $since->copy()->addDays($i)->toDateString());

        $ordersSeries = $days->map(fn (string $d) => [
            'date' => $d,
            'orders' => $byDay->get($d)?->count() ?? 0,
        ])->values();

        $revenueSeries = $days->map(fn (string $d) => [
            'date' => $d,
            'revenue' => round(($byDay->get($d) ?? collect())
                ->whereIn('status', self::REVENUE_STATUSES)
                ->sum('total_cents') / 100, 2),
        ])->values();

        // ── Status breakdown ─────────────────────────────────────────────
        $statusData = $scoped()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->map(fn ($c, $s) => [
                'status' => $s,
                'label' => ucfirst(str_replace('_', ' ', (string) $s)),
                'count' => (int) $c,
            ])
            ->values();

        // ── Top products ─────────────────────────────────────────────────
        $topProducts = OrderItem::query()
            ->whereHas('order', fn (Builder $q) => $q
                ->when($storeIds !== null, fn (Builder $qq) => $qq->whereIn('store_id', $storeIds))
                ->where('status', '!=', Order::STATUS_DRAFT))
            ->selectRaw('product_name, sum(quantity) as qty')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn (OrderItem $r) => ['name' => $r->product_name, 'qty' => (int) $r->qty]);

        $revenueCents = (int) $scoped()->whereIn('status', self::REVENUE_STATUSES)->sum('total_cents');

        // ── KPIs + alerts (role-specific) ────────────────────────────────
        if ($isAdmin) {
            $kpis = [
                ['key' => 'orders', 'label' => 'Total orders', 'value' => $scoped()->count(), 'icon' => 'shopping-bag'],
                ['key' => 'revenue', 'label' => 'Revenue', 'value' => $revenueCents, 'format' => 'money', 'icon' => 'cash'],
                ['key' => 'stores', 'label' => 'Stores', 'value' => Store::count(), 'icon' => 'building-store'],
                ['key' => 'pending', 'label' => 'Pending payment', 'value' => $scoped()->where('status', Order::STATUS_PENDING_PAYMENT)->count(), 'icon' => 'clock', 'variant' => 'warning'],
            ];

            $alerts = [];
            if (($failed = Payment::where('status', Payment::STATUS_FAILED)->count()) > 0) {
                $alerts[] = ['label' => 'Failed payments', 'count' => $failed, 'severity' => 'danger'];
            }
            $stuck = $scoped()
                ->whereIn('status', [Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])
                ->where('placed_at', '<', Carbon::now()->subDay())
                ->count();
            if ($stuck > 0) {
                $alerts[] = ['label' => 'In progress over 24h', 'count' => $stuck, 'severity' => 'warning'];
            }
        } else {
            $kpis = [
                ['key' => 'today', 'label' => 'Orders today', 'value' => $scoped()->whereDate('placed_at', Carbon::today())->count(), 'icon' => 'shopping-bag'],
                ['key' => 'revenue', 'label' => 'Revenue', 'value' => $revenueCents, 'format' => 'money', 'icon' => 'cash'],
                ['key' => 'queue', 'label' => 'In queue', 'value' => $scoped()->whereIn('status', [Order::STATUS_PAID, Order::STATUS_ACCEPTED, Order::STATUS_IN_PROGRESS])->count(), 'icon' => 'list-details'],
                ['key' => 'ready', 'label' => 'Ready for pickup', 'value' => $scoped()->where('status', Order::STATUS_READY)->count(), 'icon' => 'package', 'variant' => 'success'],
            ];

            $alerts = [];
            if (($awaiting = $scoped()->where('status', Order::STATUS_PAID)->count()) > 0) {
                $alerts[] = ['label' => 'Paid, awaiting acceptance', 'count' => $awaiting, 'severity' => 'warning'];
            }
            if (($unpaid = $scoped()->where('status', Order::STATUS_PENDING_PAYMENT)->count()) > 0) {
                $alerts[] = ['label' => 'Awaiting payment', 'count' => $unpaid, 'severity' => 'info'];
            }
        }

        return $this->success([
            'role' => $isAdmin ? 'admin' : 'store',
            'kpis' => $kpis,
            'orders_series' => $ordersSeries,
            'revenue_series' => $revenueSeries,
            'status_breakdown' => $statusData,
            'top_products' => $topProducts,
            'alerts' => $alerts,
        ]);
    }
}
