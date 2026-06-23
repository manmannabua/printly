<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Talks to each store's OWN PayMongo account (stores connect their keys; the
 * platform never holds funds — planning §10). All money is integer centavos,
 * which is also PayMongo's unit, so no conversion is needed.
 */
class PaymongoService
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.paymongo.base_url', 'https://api.paymongo.com/v1'), '/');
    }

    /**
     * Create a PayMongo checkout session for an order and record a pending payment.
     */
    public function createCheckout(Order $order): Payment
    {
        $store = $order->store;
        $secret = $store->paymongo_secret_key;

        if (empty($secret)) {
            throw new RuntimeException('Store has no PayMongo secret key configured.');
        }

        $frontend = rtrim((string) config('app.frontend_url'), '/');

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->post($this->baseUrl().'/checkout_sessions', [
                'data' => [
                    'attributes' => [
                        'line_items' => [[
                            'currency' => 'PHP',
                            'amount' => $order->total_cents,
                            'name' => "Order {$order->code}",
                            'quantity' => 1,
                        ]],
                        'payment_method_types' => $this->methodTypes($order->pay_method),
                        'description' => "Printly order {$order->code}",
                        'reference_number' => $order->code,
                        'success_url' => "{$frontend}/orders/{$order->code}",
                        'cancel_url' => "{$frontend}/orders/{$order->code}",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayMongo checkout failed: '.$response->body());
        }

        $data = $response->json('data');

        return Payment::create([
            'order_id' => $order->id,
            'store_id' => $store->id,
            'provider' => 'paymongo',
            'provider_ref' => $data['id'] ?? null,
            'checkout_url' => $data['attributes']['checkout_url'] ?? null,
            'amount_cents' => $order->total_cents,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => $data,
        ]);
    }

    /**
     * Verify a PayMongo webhook signature.
     * Header format: "t=<timestamp>,te=<test sig>,li=<live sig>".
     */
    public function verifySignature(string $payload, ?string $signatureHeader, string $secret): bool
    {
        if (empty($signatureHeader)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', $segment, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        $timestamp = $parts['t'] ?? null;
        $provided = $parts['li'] ?? ($parts['te'] ?? null);
        if (! $timestamp || ! $provided) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return hash_equals($expected, $provided);
    }

    /**
     * Refund a captured payment (full or partial). Records a Refund row.
     */
    public function refund(Payment $payment, int $amountCents, ?string $reason = null): Refund
    {
        $store = Store::findOrFail($payment->store_id);
        $secret = $store->paymongo_secret_key;
        $paymentRef = $payment->provider_payment_ref;

        if (empty($secret) || empty($paymentRef)) {
            throw new RuntimeException('Cannot refund: missing PayMongo credentials or payment reference.');
        }

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->post($this->baseUrl().'/refunds', [
                'data' => [
                    'attributes' => [
                        'amount' => $amountCents,
                        'payment_id' => $paymentRef,
                        'reason' => $this->refundReason($reason),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('PayMongo refund failed: '.$response->body());
        }

        $data = $response->json('data');

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'store_id' => $store->id,
            'amount_cents' => $amountCents,
            'reason' => $reason,
            'status' => Refund::STATUS_SUCCEEDED,
            'provider_ref' => $data['id'] ?? null,
            'raw_payload' => $data,
        ]);

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);

        return $refund;
    }

    /**
     * @return array<int, string>
     */
    private function methodTypes(?string $payMethod): array
    {
        return match ($payMethod) {
            'gcash' => ['gcash'],
            'maya' => ['paymaya'],
            'card' => ['card'],
            default => ['gcash', 'paymaya', 'card'],
        };
    }

    /** PayMongo only accepts a fixed set of refund reasons. */
    private function refundReason(?string $reason): string
    {
        return in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)
            ? $reason
            : 'others';
    }
}
