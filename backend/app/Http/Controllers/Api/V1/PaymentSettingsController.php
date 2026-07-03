<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditLog;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A store's own PayMongo connection (planning §6/§10). Each store connects its
 * OWN account, so the platform never holds funds. Secrets are write-only: they
 * are set here and encrypted at rest, but never read back — the UI only learns
 * whether a key is configured.
 */
class PaymentSettingsController extends BaseController
{
    public function show(Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        return $this->success($this->payload($store));
    }

    /**
     * Update the connection. A blank secret field leaves the stored key
     * untouched (write-only), so saving the form doesn't wipe existing keys.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $data = $request->validate([
            'payments_enabled' => ['required', 'boolean'],
            'paymongo_secret_key' => ['nullable', 'string', 'max:255'],
            'paymongo_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $attrs = ['payments_enabled' => $data['payments_enabled']];
        if ($request->filled('paymongo_secret_key')) {
            $attrs['paymongo_secret_key'] = trim($data['paymongo_secret_key']);
        }
        if ($request->filled('paymongo_webhook_secret')) {
            $attrs['paymongo_webhook_secret'] = trim($data['paymongo_webhook_secret']);
        }

        $store->update($attrs);
        // Audit records the change, never the secret values themselves.
        AuditLog::log($store, 'payment_settings_updated', null, [
            'payments_enabled' => $store->payments_enabled,
            'secret_updated' => isset($attrs['paymongo_secret_key']),
            'webhook_secret_updated' => isset($attrs['paymongo_webhook_secret']),
        ]);

        return $this->success($this->payload($store), 'Payment settings saved.');
    }

    /**
     * Disconnect: clear keys and turn payments off (falls back to cash-on-pickup).
     */
    public function destroy(Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $store->update([
            'payments_enabled' => false,
            'paymongo_secret_key' => null,
            'paymongo_webhook_secret' => null,
        ]);
        AuditLog::log($store, 'payment_settings_disconnected');

        return $this->success($this->payload($store), 'PayMongo disconnected.');
    }

    /**
     * Safe, secret-free view of the connection.
     *
     * @return array<string, mixed>
     */
    private function payload(Store $store): array
    {
        return [
            'payments_enabled' => (bool) $store->payments_enabled,
            'accepts_online' => $store->acceptsOnlinePayments(),
            'has_secret_key' => ! empty($store->paymongo_secret_key),
            'has_webhook_secret' => ! empty($store->paymongo_webhook_secret),
            'webhook_url' => route('api.v1.webhooks.paymongo', ['store' => $store->id]),
        ];
    }
}
