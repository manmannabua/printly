<script setup lang="ts">
import { ref, nextTick, watch, computed, onMounted, onUnmounted } from 'vue'
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
  (e: 'attach', file: File): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)

function onFilePicked(e: Event): void {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) emit('attach', file)
  input.value = ''
}

function isImage(mime: string | null): boolean {
  return !!mime && mime.startsWith('image/')
}

const QUICK_EMOJIS = ['👍', '❤️', '😂', '🎉', '✅']

const draft = ref('')
const scrollEl = ref<HTMLElement | null>(null)
const reactingFor = ref<string | null>(null)
// Fixed viewport coords for the (teleported) emoji picker so it can't be clipped
// by the thread's scroll container or run off the drawer edge.
const reactStyle = ref<Record<string, string>>({})
const REACT_POPOVER_WIDTH = 196

function toggleReact(m: ChatMessage, ev: MouseEvent): void {
  if (reactingFor.value === m.id) {
    reactingFor.value = null
    return
  }
  reactingFor.value = m.id
  const rect = (ev.currentTarget as HTMLElement).getBoundingClientRect()
  let left = rect.left + rect.width / 2 - REACT_POPOVER_WIDTH / 2
  left = Math.max(8, Math.min(left, window.innerWidth - REACT_POPOVER_WIDTH - 8))
  // Prefer above the button; flip below if there isn't room at the top.
  const top = rect.top - 44 < 8 ? rect.bottom + 8 : rect.top - 44
  reactStyle.value = { left: `${left}px`, top: `${top}px`, width: `${REACT_POPOVER_WIDTH}px` }
}
const editingId = ref<string | null>(null)
const editDraft = ref('')

const isMine = (m: ChatMessage) => m.sender_side === props.mySide

// Edit/delete are time-boxed: the author may only modify a message until its
// `editable_until` timestamp (enforced server-side too). A coarse ticking clock
// makes the buttons disappear once the window lapses without a re-render.
const now = ref(Date.now())
let nowTimer: ReturnType<typeof setInterval> | null = null

function canModifyMessage(m: ChatMessage): boolean {
  if (!props.canModify || !isMine(m)) return false
  return !m.editable_until || now.value < new Date(m.editable_until).getTime()
}

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

onMounted(() => {
  nowTimer = setInterval(() => { now.value = Date.now() }, 30000)
})
onUnmounted(() => {
  if (nowTimer) clearInterval(nowTimer)
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
              : 'rounded-bl-sm bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100'"
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
              <template v-else>
                <p v-if="m.body" class="whitespace-pre-wrap break-words">{{ m.body }}</p>
                <div v-for="att in m.attachments" :key="att.id" class="mt-1">
                  <a v-if="isImage(att.mime)" :href="att.url" target="_blank" rel="noopener">
                    <img :src="att.url" :alt="att.name" class="max-h-48 rounded-lg border border-black/10">
                  </a>
                  <a
                    v-else
                    :href="att.url"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs underline"
                    :class="isMine(m) ? 'bg-white/15' : 'bg-black/5 dark:bg-white/10'"
                  >
                    <AppIcon name="paperclip" :size="14" />
                    <span class="truncate">{{ att.name }}</span>
                  </a>
                </div>
              </template>
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
              class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
              title="React"
              @click="toggleReact(m, $event)"
            >
              <AppIcon name="mood-smile" :size="15" />
            </button>
            <template v-if="canModifyMessage(m)">
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" title="Edit" @click="startEdit(m)">
                <AppIcon name="pencil" :size="15" />
              </button>
              <button class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-rose-500 dark:hover:bg-gray-800" title="Delete" @click="emit('remove', m.id)">
                <AppIcon name="trash" :size="15" />
              </button>
            </template>
          </div>
        </div>

        <!-- Reaction chips -->
        <div v-if="m.reactions.length" class="mt-1 flex flex-wrap gap-1" :class="isMine(m) ? 'justify-end' : ''">
          <button
            v-for="r in m.reactions"
            :key="r.emoji"
            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-1.5 py-0.5 text-xs dark:border-gray-700 dark:bg-gray-800"
            @click="react(m.id, r.emoji)"
          >
            <span>{{ r.emoji }}</span>
            <span class="text-gray-500">{{ r.count }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Composer -->
    <div class="border-t border-gray-200 p-3 dark:border-gray-800">
      <p v-if="typingLabel" class="mb-1.5 px-1 text-xs italic text-gray-400">{{ typingLabel }}</p>
      <div class="flex items-end gap-2">
        <input ref="fileInput" type="file" class="hidden" @change="onFilePicked">
        <button
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
          title="Attach a file"
          @click="fileInput?.click()"
        >
          <AppIcon name="paperclip" :size="18" />
        </button>
        <textarea
          v-model="draft"
          rows="1"
          placeholder="Type a message…"
          class="max-h-32 flex-1 resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-cyan-600 focus:outline-none focus:ring-1 focus:ring-cyan-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
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

    <!-- Emoji picker — teleported + fixed so it never gets clipped by the scroll
         container and always stays within the viewport. -->
    <Teleport to="body">
      <template v-if="reactingFor">
        <div class="fixed inset-0 z-[9998]" @click="reactingFor = null" />
        <div
          class="fixed z-[9999] flex justify-center gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"
          :style="reactStyle"
        >
          <button
            v-for="e in QUICK_EMOJIS"
            :key="e"
            class="rounded-full px-1.5 py-0.5 text-base hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="react(reactingFor, e)"
          >{{ e }}</button>
        </div>
      </template>
    </Teleport>
  </div>
</template>
