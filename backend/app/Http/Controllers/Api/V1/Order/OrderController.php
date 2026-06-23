<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\Store;
use App\Services\OrderService;
use App\Services\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends BaseController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymongoService $paymongo,
    ) {}

    /**
     * The store queue board: orders filtered by status.
     */
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $query = Order::query()->where('store_id', $store->id)->with('items');

        $this->applyFilters($query, $request, [
            'search' => ['code', 'notes'],
            'exact' => ['status', 'payment_status'],
        ]);
        $this->applySorting($query, $request, ['placed_at', 'created_at', 'status', 'total_cents'], 'placed_at');

        return $this->paginateOrAll($query, $request, OrderResource::class);
    }

    public function store(CreateOrderRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $data = $request->validated();
        $customerId = $this->resolveCustomerId($data);

        $order = $this->orders->create($store, $customerId, $data['items'], [
            'actor_type' => OrderEvent::ACTOR_STAFF,
            'actor_id' => $request->user()?->id,
            'pay_method' => $data['pay_method'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->success(new OrderResource($order->load('items.files')), 'Order created.', 201);
    }

    public function show(Store $store, Order $order): JsonResponse
    {
        $this->ensureOwned($store, $order);

        return $this->success(new OrderResource($order->load(['items.files', 'events'])));
    }

    /**
     * Transition an order's status (accept / print / ready / complete / reject…).
     */
    public function update(UpdateOrderStatusRequest $request, Store $store, Order $order): JsonResponse
    {
        $this->ensureOwned($store, $order);

        $reason = $request->input('reason');
        $order = $this->orders->transition(
            $order,
            $request->validated()['status'],
            OrderEvent::ACTOR_STAFF,
            $request->user()?->id,
            array_filter(['reason' => $reason]),
        );

        if ($order->status === Order::STATUS_REFUNDED) {
            $this->refundIfPaid($order, $reason);
        }

        return $this->success(new OrderResource($order->fresh(['items', 'events'])), 'Order updated.');
    }

    /**
     * Best-effort PayMongo refund when an order is marked refunded. The status
     * change already succeeded; a PSP hiccup is logged, not surfaced as a 500.
     */
    private function refundIfPaid(Order $order, ?string $reason): void
    {
        $payment = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->first();

        if (! $payment) {
            return;
        }

        try {
            $this->paymongo->refund($payment, $payment->amount_cents, $reason);
        } catch (\Throwable $e) {
            Log::error('PayMongo refund failed', ['order' => $order->code, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Resolve a customer id from an explicit id or an inline guest block.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomerId(array $data): ?string
    {
        if (! empty($data['customer_id'])) {
            return $data['customer_id'];
        }

        $customer = $data['customer'] ?? null;
        if (! $customer || (empty($customer['phone']) && empty($customer['email']))) {
            return null;
        }

        // Guest keyed by phone (planning §4.1) — reuse an existing guest row.
        $existing = ! empty($customer['phone'])
            ? Customer::where('phone', $customer['phone'])->first()
            : null;

        if ($existing) {
            return $existing->id;
        }

        return Customer::create([
            'name' => $customer['name'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'email' => $customer['email'] ?? null,
            'is_guest' => true,
        ])->id;
    }

    private function ensureOwned(Store $store, Order $order): void
    {
        $this->authorizeStore($store);
        abort_unless($order->store_id === $store->id, 404, 'Order not found.');
    }
}
