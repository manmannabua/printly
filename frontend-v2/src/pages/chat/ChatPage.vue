<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useReverbChannel } from '@/composables/useReverbChannel'
import * as chatService from '@/services/chatService'
import AppIcon from '@/components/common/AppIcon.vue'
import ChatThread from '@/components/chat/ChatThread.vue'
import type { ChatConversation, ChatMessage } from '@/types/chat'

const authStore = useAuthStore()
const reverb = useReverbChannel()

const conversations = ref<ChatConversation[]>([])
const activeId = ref<string | null>(null)
const messages = ref<ChatMessage[]>([])
const loadingList = ref(true)
const loadingThread = ref(false)
const typingLabel = ref<string | null>(null)
let typingClearTimer: ReturnType<typeof setTimeout> | null = null

const active = computed(() => conversations.value.find(c => c.id === activeId.value) ?? null)
const myUserId = computed(() => authStore.user?.id ?? null)

function upsert(message: ChatMessage): void {
  const i = messages.value.findIndex(m => m.id === message.id)
  if (i !== -1) messages.value[i] = message
  else messages.value.push(message)
}

async function openConversation(c: ChatConversation): Promise<void> {
  if (activeId.value === c.id) return
  activeId.value = c.id
  loadingThread.value = true
  reverb.unsubscribeAll()

  try {
    const res = await chatService.getMessages(c.id)
    messages.value = res.data
  } finally {
    loadingThread.value = false
  }

  const channel = `chat.conversation.${c.id}`
  reverb.subscribeToPrivate<{ kind: string, message: ChatMessage }>(channel, '.message', (p) => {
    upsert(p.message)
    void markReadLatest()
  })
  reverb.subscribeToPrivate<{ message_id: string, reactions: ChatMessage['reactions'] }>(channel, '.reaction.toggled', (p) => {
    const m = messages.value.find(x => x.id === p.message_id)
    if (m) m.reactions = p.reactions
  })
  reverb.subscribeToPrivate<{ side: string, name: string | null, is_typing: boolean }>(channel, '.user.typing', (p) => {
    if (p.side === c.my_side) return
    if (typingClearTimer) clearTimeout(typingClearTimer)
    if (p.is_typing) {
      const who = p.name ?? (p.side === 'customer' ? 'Customer' : p.side === 'store' ? 'Store' : 'Admin')
      typingLabel.value = `${who} is typing…`
      typingClearTimer = setTimeout(() => { typingLabel.value = null }, 4000)
    } else {
      typingLabel.value = null
    }
  })

  c.unread_count = 0
  void markReadLatest()
}

async function onTyping(isTyping: boolean): Promise<void> {
  if (activeId.value) {
    try { await chatService.sendTyping(activeId.value, isTyping) } catch { /* ignore */ }
  }
}

async function markReadLatest(): Promise<void> {
  const last = messages.value[messages.value.length - 1]
  if (activeId.value && last) {
    try { await chatService.markRead(activeId.value, last.id) } catch { /* ignore */ }
  }
}

async function onSend(body: string): Promise<void> {
  if (!activeId.value) return
  const msg = await chatService.sendMessage(activeId.value, body)
  upsert(msg)
}

async function onReact(messageId: string, emoji: string): Promise<void> {
  await chatService.toggleReaction(messageId, emoji)
}

async function onEdit(messageId: string, body: string): Promise<void> {
  const msg = await chatService.editMessage(messageId, body)
  upsert(msg)
}

async function onRemove(messageId: string): Promise<void> {
  await chatService.deleteMessage(messageId)
}

async function onAttach(file: File): Promise<void> {
  if (!activeId.value) return
  upsert(await chatService.uploadAttachment(activeId.value, file))
}

function preview(c: ChatConversation): string {
  return c.last_message_preview ?? (c.type === 'order' ? 'Customer order chat' : 'No messages yet')
}

onMounted(async () => {
  try {
    conversations.value = await chatService.getConversations()
    const first = conversations.value[0]
    if (first) await openConversation(first)
  } finally {
    loadingList.value = false
  }
})
</script>

<template>
  <div class="flex h-[calc(100vh-7rem)] overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
    <!-- Conversation list -->
    <aside
      class="w-full shrink-0 border-r border-gray-200 dark:border-zinc-800 sm:w-72"
      :class="activeId ? 'hidden sm:block' : 'block'"
    >
      <div class="border-b border-gray-200 px-4 py-3 dark:border-zinc-800">
        <h2 class="font-display text-lg font-bold text-gray-900 dark:text-white">Messages</h2>
      </div>
      <div class="overflow-y-auto">
        <div v-if="loadingList" class="flex justify-center py-10">
          <div class="h-6 w-6 animate-spin rounded-full border-2 border-cyan-600 border-t-transparent" />
        </div>
        <p v-else-if="!conversations.length" class="px-4 py-10 text-center text-sm text-gray-400">
          No conversations yet.
        </p>
        <button
          v-for="c in conversations"
          :key="c.id"
          class="flex w-full items-center gap-3 border-b border-gray-100 px-4 py-3 text-left transition-colors dark:border-zinc-800/60"
          :class="activeId === c.id ? 'bg-cyan-50 dark:bg-cyan-950/20' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50'"
          @click="openConversation(c)"
        >
          <span
            class="flex size-9 shrink-0 items-center justify-center rounded-lg"
            :class="c.type === 'order' ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400' : 'bg-cyan-100 text-cyan-600 dark:bg-cyan-950/40 dark:text-cyan-400'"
          >
            <AppIcon :name="c.type === 'order' ? 'package' : 'building-store'" :size="17" />
          </span>
          <span class="min-w-0 flex-1">
            <span class="flex items-center justify-between gap-2">
              <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ c.title }}</span>
              <span
                v-if="c.unread_count > 0"
                class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-bold text-white"
              >{{ c.unread_count }}</span>
            </span>
            <span class="block truncate text-xs text-gray-500">{{ preview(c) }}</span>
          </span>
        </button>
      </div>
    </aside>

    <!-- Thread -->
    <section class="flex min-w-0 flex-1 flex-col" :class="activeId ? 'flex' : 'hidden sm:flex'">
      <template v-if="active">
        <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-zinc-800">
          <button class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800 sm:hidden" @click="activeId = null">
            <AppIcon name="arrow-left" :size="18" />
          </button>
          <div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ active.title }}</p>
            <p v-if="active.order_code" class="text-xs text-gray-400">Order {{ active.order_code }}</p>
          </div>
        </div>
        <ChatThread
          class="flex-1"
          :messages="messages"
          :my-side="active.my_side"
          :my-user-id="myUserId"
          :can-modify="true"
          :loading="loadingThread"
          :typing-label="typingLabel"
          @send="onSend"
          @react="onReact"
          @edit="onEdit"
          @remove="onRemove"
          @typing="onTyping"
          @attach="onAttach"
        />
      </template>
      <div v-else class="hidden flex-1 items-center justify-center text-sm text-gray-400 sm:flex">
        Select a conversation to start chatting.
      </div>
    </section>
  </div>
</template>
