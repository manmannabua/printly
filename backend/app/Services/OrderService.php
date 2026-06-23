<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Exceptions\OrderTransitionException;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the order lifecycle: creation (with a pricing snapshot) and the state
 * machine (planning §3). Every status change is recorded as an append-only
 * OrderEvent and broadcast over Reverb, so the log and the live UI never diverge.
 */
class OrderService
{
    /**
     * Legal transitions: current status => [allowed next statuses].
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        Order::STATUS_DRAFT => [Order::STATUS_PENDING_PAYMENT, Order::STATUS_CANCELLED],
        Order::STATUS_PENDING_PAYMENT => [Order::STATUS_PAID, Order::STATUS_CANCELLED],
        Order::STATUS_PAID => [Order::STATUS_ACCEPTED, Order::STATUS_REJECTED, Order::STATUS_CANCELLED],
        Order::STATUS_ACCEPTED => [Order::STATUS_IN_PROGRESS, Order::STATUS_REJECTED, Order::STATUS_FAILED],
        Order::STATUS_IN_PROGRESS => [Order::STATUS_READY, Order::STATUS_FAILED],
        Order::STATUS_READY => [Order::STATUS_COMPLETED, Order::STATUS_FAILED],
        Order::STATUS_REJECTED => [Order::STATUS_REFUNDED],
        Order::STATUS_FAILED => [Order::STATUS_REFUNDED],
        Order::STATUS_COMPLETED => [],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_REFUNDED => [],
    ];

    /** Status => timestamp column stamped on entry. */
    private const TIMESTAMP_ON = [
        Order::STATUS_ACCEPTED => 'accepted_at',
        Order::STATUS_READY => 'ready_at',
        Order::STATUS_COMPLETED => 'completed_at',
    ];

    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Create an order with a frozen pricing snapshot per line.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array{actor_type?:string, actor_id?:?string, pay_method?:?string, notes?:?string}  $meta
     */
    public function create(Store $store, ?string $customerId, array $items, array $meta = []): Order
    {
        return DB::transaction(function () use ($store, $customerId, $items, $meta) {
            $order = Order::create([
                'store_id' => $store->id,
                'customer_id' => $customerId,
                'code' => $this->generateCode(),
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => Order::PAYMENT_UNPAID,
                'pay_method' => $meta['pay_method'] ?? null,
                'placed_at' => now(),
                'notes' => $meta['notes'] ?? null,
            ]);

            $subtotal = 0;

            foreach ($items as $line) {
                $product = Product::where('store_id', $store->id)
                    ->with(['productType', 'priceRules', 'options'])
                    ->findOrFail($line['product_id']);

                $quote = $this->quoteLine($product, $line);

                $item = $order->items()->create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'pricing_mode' => $product->productType->pricing_mode,
                    'quantity' => $quote['quantity'],
                    'unit_breakdown' => $quote['breakdown'],
                    'spec_selections' => $line['selections'] ?? null,
                    'line_total_cents' => $quote['total_cents'],
                ]);

                $subtotal += $quote['total_cents'];

                $this->attachFiles($store, $item->id, $line['file_ids'] ?? []);
            }

            $order->update([
                'subtotal_cents' => $subtotal,
                'fee_cents' => 0,
                'total_cents' => $subtotal,
            ]);

            $this->recordEvent(
                $order,
                null,
                Order::STATUS_PENDING_PAYMENT,
                $meta['actor_type'] ?? OrderEvent::ACTOR_CUSTOMER,
                $meta['actor_id'] ?? null,
            );

            OrderPlaced::dispatch($order);

            $this->notifyStore($order, 'order_placed', 'New order', "Order {$order->code} was placed.");

            return $order->load('items.files');
        });
    }

    /**
     * Move an order to a new status, enforcing the state machine.
     */
    public function transition(
        Order $order,
        string $to,
        string $actorType = OrderEvent::ACTOR_STAFF,
        ?string $actorId = null,
        array $meta = [],
    ): Order {
        $from = $order->status;

        if ($from === $to) {
            throw new OrderTransitionException("Order is already {$to}.");
        }

        $allowed = self::TRANSITIONS[$from] ?? [];
        if (! in_array($to, $allowed, true)) {
            throw new OrderTransitionException("Cannot move an order from {$from} to {$to}.");
        }

        $attributes = ['status' => $to];

        if ($column = self::TIMESTAMP_ON[$to] ?? null) {
            $attributes[$column] = now();
        }
        if ($to === Order::STATUS_PAID) {
            $attributes['payment_status'] = Order::PAYMENT_PAID;
        }
        if ($to === Order::STATUS_REFUNDED) {
            $attributes['payment_status'] = Order::PAYMENT_REFUNDED;
        }

        $order->update($attributes);

        $this->recordEvent($order, $from, $to, $actorType, $actorId, $meta);

        OrderStatusChanged::dispatch($order, $from);

        if ($to === Order::STATUS_PAID) {
            $this->notifyStore($order, 'order_paid', 'Payment received', "Order {$order->code} has been paid.");
        }

        return $order;
    }

    /**
     * Notify every member of the order's store (queue board link in the payload).
     */
    private function notifyStore(Order $order, string $type, string $title, string $body): void
    {
        $users = $order->store?->users()->get();
        if (! $users) {
            return;
        }

        Notifier::sendMany($users, $type, [
            'title' => $title,
            'body' => $body,
            'url' => "/stores/{$order->store_id}/queue",
            'order_code' => $order->code,
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{total_cents:int, quantity:int, breakdown:array<int, array<string, mixed>>}
     */
    private function quoteLine(Product $product, array $line): array
    {
        if ($product->productType->pricing_mode === \App\Models\ProductType::PRICING_FILE_BASED) {
            $quote = $this->pricing->quoteFileBased($product, $line['spec'] ?? []);

            return [
                'total_cents' => $quote['total_cents'],
                'quantity' => $quote['copies'],
                'breakdown' => $quote['breakdown'],
            ];
        }

        return $this->pricing->quoteSpecBased(
            $product,
            $line['selections'] ?? [],
            (int) ($line['quantity'] ?? 1),
        );
    }

    /**
     * Link previously-uploaded, unattached files (of this store) to an item.
     *
     * @param  array<int, string>  $fileIds
     */
    private function attachFiles(Store $store, string $orderItemId, array $fileIds): void
    {
        if ($fileIds === []) {
            return;
        }

        OrderFile::where('store_id', $store->id)
            ->whereIn('id', $fileIds)
            ->whereNull('order_item_id')
            ->update(['order_item_id' => $orderItemId]);
    }

    private function recordEvent(
        Order $order,
        ?string $from,
        string $to,
        string $actorType,
        ?string $actorId,
        array $meta = [],
    ): void {
        $order->events()->create([
            'store_id' => $order->store_id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Short, unambiguous, collision-checked order code (drives the QR).
     */
    private function generateCode(): string
    {
        do {
            // Crockford-ish alphabet: no 0/O/1/I to avoid pickup-counter confusion.
            $code = 'PRT-'.Str::upper(Str::password(5, symbols: false, numbers: true, letters: true));
            $code = str_replace(['0', 'O', '1', 'I', 'l'], ['8', '9', '7', '6', '5'], $code);
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
