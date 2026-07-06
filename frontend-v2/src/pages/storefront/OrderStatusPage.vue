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
  return `PHP ${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
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

const { pause: pausePolling } = useIntervalFn(load, 8000)
let unsubscribe: (() => void) | null = null

const reverb = useReverbChannel()
const chatMessages = ref<ChatMessage[]>([])
const chatLoading = ref(true)
const chatTypingLabel = ref<string | null>(null)
const chatOpen = ref(false)
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
      chatTypingLabel.value = 'Store is typing...'
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
  <div class="min-h-[calc(100dvh-2rem)]">
    <div v-if="loading" class="flex min-h-[70dvh] flex-col items-center justify-center gap-3">
      <AppSpinner size="lg" />
      <p class="text-sm text-slate-500">Loading order...</p>
    </div>

    <div v-else-if="errorMessage" class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-zinc-800">
        <AppIcon name="alert-circle" :size="30" />
      </div>
      <p class="mt-4 font-semibold text-slate-950 dark:text-white">Order not found</p>
      <p class="mt-1 text-sm text-slate-500">{{ errorMessage }}</p>
    </div>

    <template v-else-if="order">
      <header class="sticky top-0 z-20 -mx-4 -mt-4 border-b border-slate-200/80 bg-slate-50/95 px-4 pb-3 pt-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ order.store.name }}</p>
        <div class="mt-2 flex items-center justify-between gap-3">
          <div>
            <h1 class="font-display text-2xl font-bold text-slate-950 dark:text-white">{{ statusHeadline }}</h1>
            <p class="mt-1 font-mono text-sm font-semibold tracking-widest text-slate-500">{{ order.code }}</p>
          </div>
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
            :class="isException ? 'bg-red-50 text-red-600 dark:bg-red-950/40' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50'"
          >
            <AppIcon :name="isException ? 'x-circle' : 'receipt'" :size="24" />
          </div>
        </div>
      </header>

      <div class="mt-4 grid grid-cols-[1fr_auto] gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Pickup code</p>
          <p class="mt-2 font-mono text-3xl font-black tracking-widest text-slate-950 dark:text-white">{{ order.code }}</p>
          <p class="mt-1 text-sm text-slate-500">Show this at the counter.</p>
        </div>
        <img v-if="qrDataUrl" :src="qrDataUrl" alt="Order QR code" class="h-24 w-24 rounded-2xl bg-white" width="96" height="96">
        <div v-else class="flex h-24 w-24 items-center justify-center rounded-2xl bg-slate-100 text-slate-300 dark:bg-zinc-800">
          <AppIcon name="qr-code" :size="60" />
        </div>
      </div>

      <section class="mt-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</p>
            <p class="mt-1 text-lg font-bold text-slate-950 dark:text-white">{{ statusHeadline }}</p>
          </div>
          <span
            class="rounded-full px-3 py-1 text-xs font-bold"
            :class="isException
              ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300'
              : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'"
          >
            Live
          </span>
        </div>

        <ol v-if="!isException" class="space-y-4">
          <li
            v-for="(step, i) in STEPS"
            :key="step.key"
            class="grid grid-cols-[2rem_1fr] gap-3"
            :class="i <= currentStepIndex ? 'text-slate-950 dark:text-white' : 'text-slate-300 dark:text-zinc-600'"
          >
            <span
              class="flex h-8 w-8 items-center justify-center rounded-full"
              :class="i < currentStepIndex
                ? 'bg-blue-600 text-white'
                : i === currentStepIndex
                  ? 'bg-blue-50 text-blue-700 ring-2 ring-blue-600 dark:bg-blue-950/40'
                  : 'bg-slate-100 dark:bg-zinc-800'"
            >
              <AppIcon :name="i < currentStepIndex ? 'check' : step.icon" :size="16" />
            </span>
            <span class="pt-1 text-sm font-semibold">{{ step.label }}</span>
          </li>
        </ol>

        <p v-else class="text-sm text-slate-500">
          This order will not be fulfilled. If you have already paid, a refund will be processed.
        </p>
      </section>

      <div class="mt-4 flex items-center justify-between rounded-3xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <span class="text-sm font-semibold text-slate-500">Total</span>
        <span class="text-xl font-bold text-slate-950 dark:text-white">{{ formatMoney(order.total_cents) }}</span>
      </div>

      <AppCard class="mt-4 overflow-hidden rounded-3xl p-0">
        <button class="flex w-full items-center gap-3 px-4 py-4 text-left" @click="chatOpen = !chatOpen">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-600 dark:bg-cyan-950/40">
            <AppIcon name="message-circle" :size="20" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-950 dark:text-white">Chat with the store</p>
            <p class="truncate text-xs text-slate-500">{{ chatTypingLabel ?? 'Questions about your order? Message here.' }}</p>
          </div>
          <AppIcon :name="chatOpen ? 'chevron-down' : 'chevron-right'" :size="18" class="text-slate-400" />
        </button>
        <div v-if="chatOpen" class="border-t border-slate-200 dark:border-zinc-800">
          <ChatThread
            class="h-[60dvh] max-h-[520px]"
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
        </div>
      </AppCard>

      <p class="mt-4 text-center text-xs text-slate-400">
        This page updates automatically.
      </p>
    </template>
  </div>
</template>
