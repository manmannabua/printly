<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Controllers\Api\V1\Storefront\Concerns\ResolvesStorefront;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\Product;
use App\Models\Store;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\PaymongoService;
use App\Services\PublicUploadTokenService;
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
        private readonly CustomerService $customers,
        private readonly PublicUploadTokenService $uploadTokens,
    ) {}

    public function store(PlaceOrderRequest $request, string $slug): JsonResponse
    {
        $store = $this->activeStore($slug);
        $data = $request->validated();
        $payMethod = $data['pay_method'] ?? null;

        $this->assertProductsOrderable($store->id, $data['items']);
        $this->assertFilesAuthorized($store->id, $data['items']);
        $this->assertPaymentMethodAvailable($store, $payMethod);

        $customerId = $this->customers->resolveGuest($data['customer']);

        $order = $this->orders->create($store, $customerId, $data['items'], [
            'actor_type' => OrderEvent::ACTOR_CUSTOMER,
            'pay_method' => $payMethod,
            'notes' => $data['notes'] ?? null,
        ]);

        $checkoutUrl = $this->maybeCreateCheckout($store, $order, $payMethod);
        if ($this->shouldAcceptCashPickup($store, $payMethod)) {
            $order = $this->orders->transition(
                $order,
                Order::STATUS_ACCEPTED,
                OrderEvent::ACTOR_CUSTOMER,
                $customerId,
                ['via' => 'cash_on_pickup'],
            );
        }

        return $this->success([
            'code' => $order->code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_cents' => $order->total_cents,
            'checkout_url' => $checkoutUrl,
        ], 'Order placed.', 201);
    }

    private function maybeCreateCheckout(Store $store, Order $order, ?string $payMethod): ?string
    {
        if (! in_array($payMethod, self::ONLINE_METHODS, true)) {
            return null;
        }

        try {
            return $this->paymongo->createCheckout($order)->checkout_url;
        } catch (\Throwable $e) {
            Log::error('PayMongo checkout creation failed', ['order' => $order->code, 'error' => $e->getMessage()]);

            $this->orders->transition(
                $order,
                Order::STATUS_CANCELLED,
                OrderEvent::ACTOR_SYSTEM,
                null,
                ['reason' => 'checkout_creation_failed'],
            );

            throw ValidationException::withMessages([
                'pay_method' => 'Online checkout could not be started. Please try again or choose another payment method.',
            ]);
        }
    }

    private function assertPaymentMethodAvailable(Store $store, ?string $payMethod): void
    {
        if ($payMethod === null) {
            throw ValidationException::withMessages([
                'pay_method' => 'Choose a payment method.',
            ]);
        }

        if ($payMethod === 'cash_on_pickup' && ! $this->shouldAcceptCashPickup($store, $payMethod)) {
            throw ValidationException::withMessages([
                'pay_method' => 'Pay at pickup is not available for this store.',
            ]);
        }

        if (in_array($payMethod, self::ONLINE_METHODS, true) && ! $store->acceptsOnlinePayments()) {
            throw ValidationException::withMessages([
                'pay_method' => 'Online payments are not available for this store.',
            ]);
        }
    }

    /**
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
     * @param  array<int, array<string, mixed>>  $items
     */
    private function assertFilesAuthorized(string $storeId, array $items): void
    {
        foreach ($items as $index => $item) {
            foreach (($item['file_ids'] ?? []) as $fileId) {
                $file = OrderFile::where('store_id', $storeId)
                    ->whereNull('order_item_id')
                    ->find($fileId);
                $token = $item['file_tokens'][$fileId] ?? null;

                if (! $file || ! $this->uploadTokens->isValid($file, $token)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.file_ids" => 'One or more uploaded files are unavailable.',
                    ]);
                }
            }
        }
    }

    private function shouldAcceptCashPickup(Store $store, ?string $payMethod): bool
    {
        return $payMethod === 'cash_on_pickup'
            && (bool) ($store->settings['pay_on_pickup_allowed'] ?? false);
    }
}
