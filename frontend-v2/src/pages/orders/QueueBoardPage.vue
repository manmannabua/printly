<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useIntervalFn } from '@vueuse/core'
import api, { getErrorMessage } from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Order, OrderStatus, Store } from '@/types/printly'
import type { ChatConversation, ChatMessage } from '@/types/chat'
import { transitionOrder } from '@/services/orderService'
import * as chatService from '@/services/chatService'
import { subscribeToStoreOrders } from '@/services/echo'
import { useReverbChannel } from '@/composables/useReverbChannel'
import { useOrderBoard, statusLabel } from '@/composables/useOrderBoard'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import OrderBoard from '@/components/orders/OrderBoard.vue'
import ChatThread from '@/components/chat/ChatThread.vue'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const reverb = useReverbChannel()
const storeId = String(route.params.id)

const store = ref<Store | null>(null)
const loading = ref(true)
const canProcess = computed(() => auth.can('orders.process'))
const myUserId = computed(() => auth.user?.id ?? null)

const { columns, setOrders, isValidDrop, getTargetStatus, updateOrder } = useOrderBoard()

// ── Data ──────────────────────────────────────────────────────────────────────
async function load(): Promise<void> {
  try {
    const res = await api.get<ApiResponse<Order[]>>(`/api/v1/stores/${storeId}/orders`, {
      params: { all: true, sort_by: 'placed_at', sort_dir: 'desc' },
    })
    setOrders(res.data.data)
  } catch (e) {
    toast.error(getErrorMessage(e))
  } finally {
    loading.value = false
  }
}

async function onTransition(order: Order, newStatus: OrderStatus, reason?: string): Promise<void> {
  // Optimistic: move the card now, reconcile from the server response.
  updateOrder(order.id, { status: newStatus })
  try {
    const updated = await transitionOrder(storeId, order.id, newStatus, reason)
    updateOrder(order.id, updated)
    toast.success(`Order ${order.code} → ${statusLabel(newStatus)}`)
  } catch (e) {
    toast.error(getErrorMessage(e))
    void load() // revert to server truth
  }
}

// ── Chat drawer ─────────────────────────────────────────────────────────────
const chatOrder = ref<Order | null>(null)
const chatConversation = ref<ChatConversation | null>(null)
const chatMessages = ref<ChatMessage[]>([])
const chatLoading = ref(false)
const typingLabel = ref<string | null>(null)
let typingClearTimer: ReturnType<typeof setTimeout> | null = null

function upsert(message: ChatMessage): void {
  const i = chatMessages.value.findIndex(m => m.id === message.id)
  if (i !== -1) chatMessages.value[i] = message
  else chatMessages.value.push(message)
}

async function openChat(order: Order): Promise<void> {
  chatOrder.value = order
  chatLoading.value = true
  chatMessages.value = []
  reverb.unsubscribeAll()
  typingLabel.value = null

  try {
    const conv = await chatService.getOrderConversation(order.id)
    chatConversation.value = conv
    const res = await chatService.getMessages(conv.id)
    chatMessages.value = res.data

    const channel = `chat.conversation.${conv.id}`
    reverb.subscribeToPrivate<{ kind: string, message: ChatMessage }>(channel, '.message', (p) => {
      upsert(p.message)
      void markReadLatest()
    })
    reverb.subscribeToPrivate<{ message_id: string, reactions: ChatMessage['reactions'] }>(channel, '.reaction.toggled', (p) => {
      const m = chatMessages.value.find(x => x.id === p.message_id)
      if (m) m.reactions = p.reactions
    })
    reverb.subscribeToPrivate<{ side: string, name: string | null, is_typing: boolean }>(channel, '.user.typing', (p) => {
      if (p.side === conv.my_side) return
      if (typingClearTimer) clearTimeout(typingClearTimer)
      if (p.is_typing) {
        const who = p.name ?? (p.side === 'customer' ? 'Customer' : 'Store')
        typingLabel.value = `${who} is typing…`
        typingClearTimer = setTimeout(() => { typingLabel.value = null }, 4000)
      } else {
        typingLabel.value = null
      }
    })

    void markReadLatest()
  } catch (e) {
    toast.error(getErrorMessage(e))
    closeChat()
  } finally {
    chatLoading.value = false
  }
}

function closeChat(): void {
  reverb.unsubscribeAll()
  chatOrder.value = null
  chatConversation.value = null
  chatMessages.value = []
  typingLabel.value = null
}

async function markReadLatest(): Promise<void> {
  const last = chatMessages.value[chatMessages.value.length - 1]
  if (chatConversation.value && last) {
    try { await chatService.markRead(chatConversation.value.id, last.id) } catch { /* ignore */ }
  }
}

async function onSend(body: string): Promise<void> {
  if (!chatConversation.value) return
  upsert(await chatService.sendMessage(chatConversation.value.id, body))
}

async function onReact(messageId: string, emoji: string): Promise<void> {
  await chatService.toggleReaction(messageId, emoji)
}

async function onEdit(messageId: string, body: string): Promise<void> {
  upsert(await chatService.editMessage(messageId, body))
}

async function onRemove(messageId: string): Promise<void> {
  await chatService.deleteMessage(messageId)
}

async function onAttach(file: File): Promise<void> {
  if (!chatConversation.value) return
  upsert(await chatService.uploadAttachment(chatConversation.value.id, file))
}

async function onTyping(isTyping: boolean): Promise<void> {
  if (chatConversation.value) {
    try { await chatService.sendTyping(chatConversation.value.id, isTyping) } catch { /* ignore */ }
  }
}

// ── Real-time (with polling fallback) ──────────────────────────────────────────
let unsubscribe: (() => void) | null = null
const { pause: stopPolling } = useIntervalFn(load, 12000)

onMounted(async () => {
  try {
    const res = await api.get<ApiResponse<Store>>(`/api/v1/stores/${storeId}`)
    store.value = res.data.data
  } catch { /* header is non-critical */ }

  await load()

  unsubscribe = subscribeToStoreOrders(storeId, {
    onPlaced: () => load(),
    onStatusChanged: () => load(),
  })
})

onUnmounted(() => {
  stopPolling()
  unsubscribe?.()
  if (typingClearTimer) clearTimeout(typingClearTimer)
})
</script>

<template>
  <div>
    <AppPageHeader
      :title="store ? `${store.name} — Queue` : 'Order Queue'"
      subtitle="Drag orders across the workflow to update their status"
      :breadcrumbs="[{ label: 'Stores', to: '/stores' }, { label: 'Queue' }]"
    >
      <template #actions>
        <AppButton variant="secondary" icon="refresh" @click="load">
          <span>Refresh</span>
        </AppButton>
      </template>
    </AppPageHeader>

    <div v-if="loading" class="flex justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <OrderBoard
      v-else
      :columns="columns"
      :is-valid-drop="isValidDrop"
      :get-target-status="getTargetStatus"
      :can-process="canProcess"
      @transition="onTransition"
      @chat="openChat"
    />

    <!-- Chat drawer -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="chatOrder" class="fixed inset-0 z-[9998]" @keydown.esc="closeChat">
          <div class="absolute inset-0 bg-black/40" @click="closeChat" />
          <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
            appear
          >
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl dark:bg-zinc-900 sm:w-[26rem]">
              <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-zinc-800">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">Customer chat</p>
                  <p class="font-mono text-xs text-gray-400">Order {{ chatOrder.code }}</p>
                </div>
                <button
                  class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 dark:hover:bg-zinc-800"
                  title="Close"
                  @click="closeChat"
                >
                  <AppIcon name="x-circle" :size="20" />
                </button>
              </div>
              <ChatThread
                class="min-h-0 flex-1"
                :messages="chatMessages"
                :my-side="chatConversation?.my_side ?? 'store'"
                :my-user-id="myUserId"
                :can-modify="true"
                :loading="chatLoading"
                :typing-label="typingLabel"
                empty-hint="No messages yet. Reach out to the customer."
                @send="onSend"
                @react="onReact"
                @edit="onEdit"
                @remove="onRemove"
                @typing="onTyping"
                @attach="onAttach"
              />
            </aside>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
