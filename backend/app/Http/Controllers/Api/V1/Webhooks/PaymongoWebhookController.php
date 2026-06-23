<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\Store;
use App\Services\OrderService;
use App\Services\PaymongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymongoWebhookController extends BaseController
{
    public function __construct(
        private readonly PaymongoService $paymongo,
        private readonly OrderService $orders,
    ) {}

    /**
     * Per-store PayMongo webhook. The store id is in the URL because each store
     * uses its own account (and thus its own webhook signing secret).
     */
    public function __invoke(Request $request, Store $store): JsonResponse
    {
        $secret = $store->paymongo_webhook_secret;
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature');

        abort_unless(
            ! empty($secret) && $this->paymongo->verifySignature($payload, $signature, $secret),
            401,
            'Invalid signature.',
        );

        $event = $request->json('data.attributes', []);
        $type = $event['type'] ?? '';

        if ($type === 'checkout_session.payment.paid') {
            $this->handlePaid($store, $event);
        }

        // Always 200 so PayMongo doesn't retry on events we intentionally ignore.
        return $this->success(null, 'ok');
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handlePaid(Store $store, array $event): void
    {
        $sessionId = $event['data']['id'] ?? null;
        $paymentRef = $event['data']['attributes']['payments'][0]['id'] ?? null;

        $payment = Payment::where('store_id', $store->id)
            ->where('provider_ref', $sessionId)
            ->first();

        if (! $payment || $payment->status === Payment::STATUS_PAID) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'provider_payment_ref' => $paymentRef,
            'paid_at' => now(),
            'raw_payload' => $event,
        ]);

        $order = $payment->order;
        if ($order && $order->status === Order::STATUS_PENDING_PAYMENT) {
            $this->orders->transition($order, Order::STATUS_PAID, OrderEvent::ACTOR_SYSTEM, null, ['via' => 'paymongo']);
        }
    }
}
