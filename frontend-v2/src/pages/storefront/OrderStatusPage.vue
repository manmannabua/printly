<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useIntervalFn } from '@vueuse/core'
import QRCode from 'qrcode'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import { getPublicOrder, getOrderChat, sendOrderChat, reactOrderChat, markOrderChatRead, sendOrderTyping, uploadOrderChatFile } from '@/services/storefrontService'
import { subscribeToOrder } from '@/services/echo'
import { useReverbChannel } from '@/composables/useReverbChannel'
import { getErrorMessage } from '@/services/api'
import AppCard from '@/components/ui/AppCard.vue'
import ChatThread from '@/components/chat/ChatThread.vue'
import type { OrderStatus, PublicOrder } from '@/types/printly'
import type { ChatMessage } from '@/types/chat'

const route = useRoute()
const code = String(route.params.code)

const order = ref<PublicOrder | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const qrDataUrl = ref('')

// The customer-facing lifecycle, in order. Terminal/exception states are handled
// separately so we don't pretend a cancelled order is "in progress".
const STEPS: { key: OrderStatus, label: string, icon: string }[] = [
  { key: 'pending_payment', label: 'Placed', icon: 'receipt' },
  { key: 'paid', label: 'Paid', icon: 'credit-card' },
  { key: 'accepted', label: 'Accepted', icon: 'check-circle' },
  { key: 'in_progress', label: 'Printing', icon: 'printer' },
  { key: 'ready', label: 'Ready for pickup', icon: 'package' },
  { key: 'completed', label: 'Completed', icon: 'badge-check' },
]

const EXCEPTION_LABELS: Partial<Record<OrderStatus, string>> = {
  cancelled: 'Cancelled',
  rejected: 'Rejected by store',
  failed: 'Print failed',
  refunded: 'Refunded',
}

const currentStepIndex = computed(() => {
  if (!order.value) return -1
  return STEPS.findIndex(s => s.key === order.value!.status)
})

const isException = computed(() => order.value !== null && order.value.status in EXCEPTION_LABELS)
const isTerminal = computed(() =>
  order.value !== null
  && (order.value.status === 'completed' || isException.value),
)

const statusHeadline = computed(() => {
  if (!order.value) return ''
  if (isException.value) return EXCEPTION_LABELS[order.value.status]!
  const step = STEPS[currentStepIndex.value]
  return step ? step.label : order.value.status
})

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

async function load(): Promise<void> {
  try {
    order.value = await getPublicOrder(code)
    errorMessage.value = ''
    if (isTerminal.value) pausePolling()
  } catch (e) {
    errorMessage.value = getErrorMessage(e)
    pausePolling()
  } finally {
    loading.value = false
  }
}

// Real-time updates over WebSocket; polling stays as a fallback when Reverb is
// unavailable (the page must keep working without it).
const { pause: pausePolling } = useIntervalFn(load, 8000)
let unsubscribe: (() => void) | null = null

// ── Customer ↔ store chat (public, code-scoped) ─────────────────────────────
const reverb = useReverbChannel()
const chatMessages = ref<ChatMessage[]>([])
const chatLoading = ref(true)
const chatTypingLabel = ref<string | null>(null)
let chatTypingTimer: ReturnType<typeof setTimeout> | null = null

function upsertChat(message: ChatMessage): void {
  const i = chatMessages.value.findIndex(m => m.id === message.id)
  if (i !== -1) chatMessages.value[i] = message
  else chatMessages.value.push(message)
}

async function loadChat(): Promise<void> {
  try {
    const { messages } = await getOrderChat(code)
    chatMessages.value = messages
    void markChatRead()
  } catch {
    /* chat is optional; ignore */
  } finally {
    chatLoading.value = false
  }
}

async function markChatRead(): Promise<void> {
  const last = chatMessages.value[chatMessages.value.length - 1]
  if (last) {
    try { await markOrderChatRead(code, last.id) } catch { /* ignore */ }
  }
}

async function onChatSend(body: string): Promise<void> {
  upsertChat(await sendOrderChat(code, body))
}

async function onChatReact(messageId: string, emoji: string): Promise<void> {
  await reactOrderChat(code, messageId, emoji)
}

async function onChatTyping(isTyping: boolean): Promise<void> {
  try { await sendOrderTyping(code, isTyping) } catch { /* ignore */ }
}

async function onChatAttach(file: File): Promise<void> {
  upsertChat(await uploadOrderChatFile(code, file))
}

onMounted(async () => {
  await load()
  unsubscribe = subscribeToOrder(code, () => { void load() })

  void loadChat()
  reverb.subscribeToPublic<{ kind: string, message: ChatMessage }>(`chat.order.${code}`, '.message', (p) => {
    upsertChat(p.message)
    void markChatRead()
  })
  reverb.subscribeToPublic<{ message_id: string, reactions: ChatMessage['reactions'] }>(`chat.order.${code}`, '.reaction.toggled', (p) => {
    const m = chatMessages.value.find(x => x.id === p.message_id)
    if (m) m.reactions = p.reactions
  })
  reverb.subscribeToPublic<{ side: string, is_typing: boolean }>(`chat.order.${code}`, '.user.typing', (p) => {
    if (p.side === 'customer') return
    if (chatTypingTimer) clearTimeout(chatTypingTimer)
    if (p.is_typing) {
      chatTypingLabel.value = 'Store is typing…'
      chatTypingTimer = setTimeout(() => { chatTypingLabel.value = null }, 4000)
    } else {
      chatTypingLabel.value = null
    }
  })

  try {
    qrDataUrl.value = await QRCode.toDataURL(window.location.href, { width: 220, margin: 1 })
  } catch {
    qrDataUrl.value = ''
  }
})

onUnmounted(() => {
  pausePolling()
  unsubscribe?.()
})
</script>

<template>
  <div class="py-6">
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="errorMessage" class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-zinc-800 dark:bg-zinc-900">
      <AppIcon name="alert-circle" :size="40" class="mx-auto text-gray-400" />
      <p class="mt-3 font-medium text-gray-900 dark:text-white">Order not found</p>
      <p class="mt-1 text-sm text-gray-500">{{ errorMessage }}</p>
    </div>

    <template v-else-if="order">
      <!-- Ticket -->
      <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <p class="text-xs uppercase tracking-wide text-gray-400">{{ order.store.name }}</p>
        <div class="mt-3 flex justify-center">
          <img v-if="qrDataUrl" :src="qrDataUrl" alt="Order QR code" class="rounded-lg" width="180" height="180">
          <div v-else class="flex h-[180px] w-[180px] items-center justify-center text-gray-300">
            <AppIcon name="qr-code" :size="120" />
          </div>
        </div>
        <p class="mt-4 font-mono text-2xl font-bold tracking-widest text-gray-900 dark:text-white">{{ order.code }}</p>
        <p class="mt-1 text-sm text-gray-500">Show this code at the counter</p>
      </div>

      <!-- Status -->
      <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div
          class="mb-4 inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium"
          :class="isException
            ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
            : 'bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-300'"
        >
          <AppIcon :name="isException ? 'x-circle' : 'circle-dot'" :size="16" />
          {{ statusHeadline }}
        </div>

        <ol v-if="!isException" class="space-y-3">
          <li
            v-for="(step, i) in STEPS"
            :key="step.key"
            class="flex items-center gap-3"
            :class="i <= currentStepIndex ? 'text-gray-900 dark:text-white' : 'text-gray-300 dark:text-zinc-600'"
          >
            <span
              class="flex h-8 w-8 items-center justify-center rounded-full"
              :class="i < currentStepIndex
                ? 'bg-primary-600 text-white'
                : i === currentStepIndex
                  ? 'bg-primary-100 text-primary-700 ring-2 ring-primary-600 dark:bg-primary-950'
                  : 'bg-gray-100 dark:bg-zinc-800'"
            >
              <AppIcon :name="i < currentStepIndex ? 'check' : step.icon" :size="16" />
            </span>
            <span class="text-sm font-medium">{{ step.label }}</span>
          </li>
        </ol>

        <p v-else class="text-sm text-gray-500">
          This order won’t be fulfilled. If you’ve already paid, a refund will be processed.
        </p>
      </div>

      <!-- Total -->
      <div class="mt-4 flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <span class="text-sm text-gray-500">Total</span>
        <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ formatMoney(order.total_cents) }}</span>
      </div>

      <!-- Chat with the store -->
      <AppCard class="mt-4 overflow-hidden p-0">
        <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-zinc-800">
          <AppIcon name="message-circle" :size="18" class="text-cyan-600" />
          <p class="text-sm font-semibold text-gray-900 dark:text-white">Chat with the store</p>
        </div>
        <ChatThread
          class="h-80"
          :messages="chatMessages"
          my-side="customer"
          :loading="chatLoading"
          :typing-label="chatTypingLabel"
          empty-hint="Questions about your order? Message the store here."
          @send="onChatSend"
          @react="onChatReact"
          @typing="onChatTyping"
          @attach="onChatAttach"
        />
      </AppCard>

      <p class="mt-4 text-center text-xs text-gray-400">
        This page updates automatically.
      </p>
    </template>
  </div>
</template>
