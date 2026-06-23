<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StorefrontOrderController extends BaseController
{
    use ResolvesStorefront;

    private const ONLINE_METHODS = ['gcash', 'card', 'maya'];

    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymongoService $paymongo,
    ) {}

    /**
     * Guest checkout (planning §7, POST /s/{slug}/orders). Creates a
     * pending_payment order keyed to a guest customer and returns the QR code +
     * status. Payment intent / cash-on-pickup advancement arrives with step 7.
     */
    public function store(PlaceOrderRequest $request, string $slug): JsonResponse
    {
        $store = $this->activeStore($slug);
        $data = $request->validated();

        $this->assertProductsOrderable($store->id, $data['items']);

        $customerId = $this->resolveGuest($data['customer']);

        $order = $this->orders->create($store, $customerId, $data['items'], [
            'actor_type' => OrderEvent::ACTOR_CUSTOMER,
            'pay_method' => $data['pay_method'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $checkoutUrl = $this->maybeCreateCheckout($store, $order, $data['pay_method'] ?? null);

        return $this->success([
            'code' => $order->code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_cents' => $order->total_cents,
            'checkout_url' => $checkoutUrl,
        ], 'Order placed.', 201);
    }

    /**
     * For online methods on a payment-enabled store, open a PayMongo checkout.
     * A PSP failure must not lose the order — it stays pending_payment and the
     * customer can be sent a fresh link, so we degrade to a null checkout URL.
     */
    private function maybeCreateCheckout(\App\Models\Store $store, Order $order, ?string $payMethod): ?string
    {
        if (! in_array($payMethod, self::ONLINE_METHODS, true) || ! $store->acceptsOnlinePayments()) {
            return null;
        }

        try {
            return $this->paymongo->createCheckout($order)->checkout_url;
        } catch (\Throwable $e) {
            Log::error('PayMongo checkout creation failed', ['order' => $order->code, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Every ordered product must be active and belong to this store.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function assertProductsOrderable(string $storeId, array $items): void
    {
        $ids = collect($items)->pluck('product_id')->filter()->unique();

        $orderable = Product::where('store_id', $storeId)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id');

        if ($orderable->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'items' => 'One or more products are unavailable.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function resolveGuest(array $customer): string
    {
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
}
