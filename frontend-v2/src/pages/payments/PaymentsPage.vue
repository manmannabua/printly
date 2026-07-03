<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import {
  getPaymentSettings, updatePaymentSettings, disconnectPayments,
  type PaymentSettings,
} from '@/services/paymentSettingsService'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog.vue'

const route = useRoute()
const toast = useToast()
const storeId = String(route.params.id)

const loading = ref(true)
const saving = ref(false)
const settings = ref<PaymentSettings | null>(null)

// Write-only inputs — blank means "keep the existing key".
const secretKey = ref('')
const webhookSecret = ref('')
const enabled = ref(false)
const disconnecting = ref(false)

async function load() {
  settings.value = await getPaymentSettings(storeId)
  enabled.value = settings.value.payments_enabled
}

onMounted(async () => {
  try { await load() } catch { toast.error('Could not load payment settings.') } finally { loading.value = false }
})

async function save() {
  saving.value = true
  try {
    settings.value = await updatePaymentSettings(storeId, {
      payments_enabled: enabled.value,
      ...(secretKey.value.trim() ? { paymongo_secret_key: secretKey.value.trim() } : {}),
      ...(webhookSecret.value.trim() ? { paymongo_webhook_secret: webhookSecret.value.trim() } : {}),
    })
    enabled.value = settings.value.payments_enabled
    secretKey.value = ''
    webhookSecret.value = ''
    toast.success('Payment settings saved.')
  } catch {
    toast.error('Could not save. Check the keys and try again.')
  } finally {
    saving.value = false
  }
}

async function confirmDisconnect() {
  disconnecting.value = false
  try {
    settings.value = await disconnectPayments(storeId)
    enabled.value = false
    secretKey.value = ''
    webhookSecret.value = ''
    toast.success('PayMongo disconnected.')
  } catch { toast.error('Could not disconnect.') }
}

async function copyWebhook() {
  if (!settings.value) return
  try { await navigator.clipboard.writeText(settings.value.webhook_url); toast.success('Webhook URL copied.') } catch { /* noop */ }
}
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <AppPageHeader title="Payments" subtitle="Connect your own PayMongo account to accept GCash, Maya, and card online." />

    <div v-if="loading" class="py-16 text-center text-sm text-gray-500">Loading…</div>

    <template v-else-if="settings">
      <!-- Connection status -->
      <AppCard class="mb-6">
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <AppIcon name="credit-card" class="h-5 w-5 text-gray-400" />
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">PayMongo</span>
            <AppBadge :variant="settings.accepts_online ? 'success' : 'neutral'">
              {{ settings.accepts_online ? 'Connected' : 'Not connected' }}
            </AppBadge>
          </div>
          <AppButton
            v-if="settings.has_secret_key"
            size="sm" variant="secondary" @click="disconnecting = true"
          ><span>Disconnect</span></AppButton>
        </div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          You keep 100% of what customers pay — money goes straight to your PayMongo balance, not to Printly.
          Without a connection, orders fall back to <strong>cash on pickup</strong>.
        </p>
      </AppCard>

      <!-- Keys -->
      <AppCard title="API keys" class="mb-6">
        <div class="space-y-4">
          <div>
            <AppInput
              v-model="secretKey" type="password" label="PayMongo secret key"
              :placeholder="settings.has_secret_key ? '•••••••••• (leave blank to keep)' : 'sk_live_… or sk_test_…'"
              autocomplete="off"
            />
            <p class="mt-1 text-xs text-gray-500">From PayMongo → Developers → API Keys. Stored encrypted; never shown again.</p>
          </div>
          <div>
            <AppInput
              v-model="webhookSecret" type="password" label="Webhook signing secret"
              :placeholder="settings.has_webhook_secret ? '•••••••••• (leave blank to keep)' : 'whsec_…'"
              autocomplete="off"
            />
            <p class="mt-1 text-xs text-gray-500">Shown once when you create the webhook (next step). Used to verify events are really from PayMongo.</p>
          </div>
          <AppToggle v-model="enabled" label="Accept online payments" />
          <p v-if="enabled && !settings.has_secret_key && !secretKey" class="text-xs text-warning-600">
            Add a secret key to actually take online payments — otherwise checkout stays cash-only.
          </p>
        </div>
        <template #footer>
          <AppButton icon="check" :loading="saving" @click="save"><span>Save</span></AppButton>
        </template>
      </AppCard>

      <!-- Webhook registration -->
      <AppCard title="Register this webhook in PayMongo">
        <ol class="mb-3 list-decimal space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300">
          <li>In PayMongo → Developers → Webhooks, add a webhook.</li>
          <li>Paste the URL below and subscribe to <code class="text-xs">checkout_session.payment.paid</code>.</li>
          <li>Copy the signing secret PayMongo shows you into the field above and save.</li>
        </ol>
        <div class="flex items-center gap-2 rounded-md bg-gray-100 p-3 dark:bg-gray-800">
          <code class="min-w-0 flex-1 break-all text-xs">{{ settings.webhook_url }}</code>
          <AppButton size="sm" variant="secondary" icon="clipboard" @click="copyWebhook"><span>Copy</span></AppButton>
        </div>
      </AppCard>
    </template>

    <AppConfirmDialog
      :model-value="disconnecting" danger title="Disconnect PayMongo?"
      message="Online payments will stop and your saved keys will be removed. New orders will be cash on pickup only."
      confirm-label="Disconnect"
      @update:model-value="(v: boolean) => { if (!v) disconnecting = false }" @confirm="confirmDisconnect"
    />
  </div>
</template>
