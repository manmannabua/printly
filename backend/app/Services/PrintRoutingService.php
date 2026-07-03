<?php

namespace App\Services;

use App\Events\PrintJobUpdated;
use App\Exceptions\OrderTransitionException;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\PrintJob;
use App\Models\Printer;
use Illuminate\Support\Facades\DB;

/**
 * The auto-print bridge (planning §5.3). Turns an accepted order into per-file
 * print_jobs routed to a capable printer, and folds agent-reported job status
 * back into the order state machine (accepted → in_progress → ready).
 */
class PrintRoutingService
{
    /**
     * Create print jobs for an accepted order, if the store has auto-print on
     * and at least one active printer. Idempotent — a second call is a no-op.
     */
    public function enqueueForOrder(Order $order): void
    {
        $store = $order->store;

        if ($store === null || ! $store->autoPrintEnabled()) {
            return;
        }

        // Already enqueued (e.g. re-entry) — never double-print.
        if ($order->printJobs()->exists()) {
            return;
        }

        $printers = $store->printers()->where('is_active', true)->get();
        if ($printers->isEmpty()) {
            return;
        }

        // Every analysed, printable file on the order (spec-based items such as
        // tarpaulins carry no files and are simply skipped).
        $files = OrderFile::query()
            ->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))
            ->with('orderItem')
            ->get();

        DB::transaction(function () use ($order, $store, $printers, $files) {
            foreach ($files as $file) {
                $printer = $this->routeToPrinter($printers, $file);

                $job = PrintJob::create([
                    'store_id' => $store->id,
                    'order_id' => $order->id,
                    'order_item_id' => $file->order_item_id,
                    'order_file_id' => $file->id,
                    'printer_id' => $printer?->id,
                    'status' => PrintJob::STATUS_QUEUED,
                    'copies' => max(1, (int) ($file->orderItem->quantity ?? 1)),
                ]);

                PrintJobUpdated::dispatch($job);
            }
        });
    }

    /**
     * Fold the order's job statuses back into its lifecycle:
     *  - any job actively printing  → order in_progress
     *  - every job finished (≥1 done) → order ready
     * Errors are left for staff to retry and never auto-advance the order.
     */
    public function syncOrderFromJobs(Order $order): void
    {
        $jobs = $order->printJobs()->get();
        if ($jobs->isEmpty()) {
            return;
        }

        $orders = app(OrderService::class);

        $anyActive = $jobs->contains(fn (PrintJob $j) => in_array($j->status, PrintJob::ACTIVE, true));
        $allTerminal = $jobs->every(fn (PrintJob $j) => in_array($j->status, PrintJob::TERMINAL, true));
        $anyDone = $jobs->contains(fn (PrintJob $j) => $j->status === PrintJob::STATUS_DONE);

        if ($order->status === Order::STATUS_ACCEPTED && ($anyActive || $allTerminal)) {
            $this->safeTransition($orders, $order, Order::STATUS_IN_PROGRESS);
        }

        if ($order->fresh()->status === Order::STATUS_IN_PROGRESS && $allTerminal && $anyDone) {
            $this->safeTransition($orders, $order, Order::STATUS_READY);
        }
    }

    /**
     * Pick the best printer for a file: prefer one that can print its paper size
     * (and colour if needed); otherwise fall back to the first active printer so
     * the job is never orphaned.
     */
    private function routeToPrinter($printers, OrderFile $file): ?Printer
    {
        $needsColor = (bool) ($file->is_color ?? false);

        return $printers->first(fn (Printer $p) => $p->canPrint($file->paper_size, $needsColor))
            ?? $printers->first();
    }

    private function safeTransition(OrderService $orders, Order $order, string $to): void
    {
        try {
            $orders->transition($order, $to, OrderEvent::ACTOR_SYSTEM, null, ['auto' => 'print_agent']);
        } catch (OrderTransitionException) {
            // The order moved on by another path (staff action, race) — fine.
        }
    }
}
