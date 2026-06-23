<script setup lang="ts">
import { ref, nextTick, watch, computed } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'
import type { ChatMessage, ChatSide } from '@/types/chat'

const props = withDefaults(defineProps<{
  messages: ChatMessage[]
  mySide: ChatSide
  myUserId?: string | null
  canModify?: boolean
  loading?: boolean
  emptyHint?: string
  typingLabel?: string | null
}>(), {
  myUserId: null,
  canModify: false,
  loading: false,
  emptyHint: 'No messages yet. Say hello!',
  typingLabel: null,
})

const emit = defineEmits<{
  (e: 'send', body: string): void
  (e: 'react', messageId: string, emoji: string): void
  (e: 'edit', messageId: string, body: string): void
  (e: 'remove', messageId: string): void
  (e: 'typing', isTyping: boolean): void
}>()

const QUICK_EMOJIS = ['👍', '❤️', '😂', '🎉', '✅']

const draft = ref('')
const scrollEl = ref<HTMLElement | null>(null)
const reactingFor = ref<string | null>(null)
const editingId = ref<string | null>(null)
const editDraft = ref('')

const isMine = (m: ChatMessage) => m.sender_side === props.mySide

// Typing indicator: emit typing(true) on first keystroke, typing(false) after a
// short idle window (or on send).
let typingActive = false
let typingTimer: ReturnType<typeof setTimeout> | null = null

function stopTyping(): void {
  if (typingTimer) { clearTimeout(typingTimer); typingTimer = null }
  if (typingActive) {
    typingActive = false
    emit('typing', false)
  }
}

function onInput(): void {
  if (!typingActive) {
    typingActive = true
    emit('typing', true)
  }
  if (typingTimer) clearTimeout(typingTimer)
  typingTimer = setTimeout(stopTyping, 2500)
}

function send(): void {
  const body = draft.value.trim()
  if (!body) return
  emit('send', body)
  draft.value = ''
  stopTyping()
}

function onKeydown(e: KeyboardEvent): void {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    send()
  }
}

function startEdit(m: ChatMessage): void {
  editingId.value = m.id
  editDraft.value = m.body ?? ''
}
function saveEdit(): void {
  if (editingId.value && editDraft.value.trim()) {
    emit('edit', editingId.value, editDraft.value.trim())
  }
  editingId.value = null
}

function react(messageId: string, emoji: string): void {
  emit('react', messageId, emoji)
  reactingFor.value = null
}

function time(iso: string): string {
  return new Date(iso).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
}

const hasMessages = computed(() => props.messages.length > 0)

watch(() => props.messages.length, async () => {
  await nextTick()
  if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight
})
</script>

<template>
  <div class="flex h-full flex-col">
    <!-- Messages -->
    <div ref="scrollEl" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
      <div v-if="loading" class="flex justify-center py-10">
        <div class="h-6 w-6 animate-spin rounded-full border-2 border-cyan-600 border-t-transparent" />
      </div>
      <p v-else-if="!hasMessages" class="py-10 text-center text-sm text-gray-400">{{ emptyHint }}</p>

      <div
        v-for="m in messages"
        :key="m.id"
        class="group flex flex-col"
        :class="isMine(m) ? 'items-end' : 'items-start'"
      >
        <p v-if="!isMine(m) && m.sender_name" class="mb-0.5 px-1 text-[11px] font-medium text-gray-400">
          {{ m.sender_name }}
        </p>

        <div class="flex items-end gap-1.5" :class="isMine(m) ? 'flex-row-reverse' : ''">
          <!-- Bubble -->
          <div
            class="max-w-[78%] rounded-2xl px-3.5 py-2 text-sm"
            :class="isMine(m)
              ? 'rounded-br-sm bg-cyan-600 text-white'
              : 'rounded-bl-sm bg-gray-100 text-gray-900 dark:bg-zinc-800 dark:text-gray-100'"
          >
            <template v-if="editingId === m.id">
              <textarea
                v-model="editDraft"
                rows="2"
                class="w-full resize-none rounded bg-white/90 p-1 text-sm text-gray-900"
                @keydown.enter.prevent="saveEdit"
              />
              <div class="mt-1 flex justify-end gap-2 text-xs">
                <button class="opacity-80 hover:opacity-100" @click="editingId = null">Cancel</button>
                <button class="font-semibold" @click="saveEdit">Save</button>
              </div>
            </template>
            <template v-else>
              <p v-if="m.is_deleted" class="italic opacity-70">Message deleted</p>
              <p v-else class="whitespace-pre-wrap break-words">{{ m.body }}</p>
              <span
                class="mt-0.5 block text-[10px]"
                :class="isMine(m) ? 'text-cyan-100/80' : 'text-gray-400'"
              >
                {{ time(m.created_at) }}<span v-if="m.edited_at"> · edited</span>
              </span>
            </template>
          </div>

          <!-- Hover actions -->
          <div v-if="!m.is_deleted && editingId !== m.id" class="relative flex items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
            <button
              class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-zinc-800"
              title="React"
              @click="reactingFor = reactingFor === m.id ? null : m.id"
            >
              <AppIcon name="mood-smile" :size="15" />
            </button>
            <template v-if="canModify && isMine(m)">
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-zinc-800" title="Edit" @click="startEdit(m)">
                <AppIcon name="pencil" :size="15" />
              </button>
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-rose-500 dark:hover:bg-zinc-800" title="Delete" @click="emit('remove', m.id)">
                <AppIcon name="trash" :size="15" />
              </button>
            </template>

            <div
              v-if="reactingFor === m.id"
              class="absolute bottom-7 z-10 flex gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
              :class="isMine(m) ? 'right-0' : 'left-0'"
            >
              <button
                v-for="e in QUICK_EMOJIS"
                :key="e"
                class="rounded-full px-1.5 py-0.5 text-base hover:bg-gray-100 dark:hover:bg-zinc-800"
                @click="react(m.id, e)"
              >{{ e }}</button>
            </div>
          </div>
        </div>

        <!-- Reaction chips -->
        <div v-if="m.reactions.length" class="mt-1 flex flex-wrap gap-1" :class="isMine(m) ? 'justify-end' : ''">
          <button
            v-for="r in m.reactions"
            :key="r.emoji"
            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-1.5 py-0.5 text-xs dark:border-zinc-700 dark:bg-zinc-800"
            @click="react(m.id, r.emoji)"
          >
            <span>{{ r.emoji }}</span>
            <span class="text-gray-500">{{ r.count }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Composer -->
    <div class="border-t border-gray-200 p-3 dark:border-zinc-800">
      <p v-if="typingLabel" class="mb-1.5 px-1 text-xs italic text-gray-400">{{ typingLabel }}</p>
      <div class="flex items-end gap-2">
        <textarea
          v-model="draft"
          rows="1"
          placeholder="Type a message…"
          class="max-h-32 flex-1 resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-cyan-600 focus:outline-none focus:ring-1 focus:ring-cyan-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100"
          @keydown="onKeydown"
          @input="onInput"
        />
        <button
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cyan-600 text-white transition-colors hover:bg-cyan-700 disabled:opacity-50"
          :disabled="!draft.trim()"
          @click="send"
        >
          <AppIcon name="send" :size="17" />
        </button>
      </div>
    </div>
  </div>
</template>
