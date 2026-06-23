<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '@/components/common/AppIcon.vue'
import { useNotifications } from '@/composables/useNotifications'
import { useToast } from '@/composables/useToast'
import type { Notification } from '@/types/notification'

const router = useRouter()
const toast = useToast()
const {
  notifications,
  unreadCount,
  loading,
  fetchNotifications,
  fetchUnreadCount,
  markAsRead,
  markAllAsRead,
  subscribeToRealtime,
} = useNotifications({
  onNotificationReceived: (n) => toast.info(n.data.title ?? 'New notification'),
})

const isOpen = ref(false)
const displayed = computed(() => notifications.value.slice(0, 10))

// type → icon/tone. Falls back to a neutral bell for unknown types.
const META: Record<string, { icon: string, tone: string }> = {
  order_placed: { icon: 'shopping-bag', tone: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' },
  order_paid: { icon: 'cash', tone: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' },
  order_status: { icon: 'refresh', tone: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400' },
  chat_message: { icon: 'message-circle', tone: 'bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400' },
}
function meta(type: string) {
  return META[type] ?? { icon: 'bell', tone: 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400' }
}

function toggle(): void {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications({ per_page: 10 })
    fetchUnreadCount()
  }
}

async function onClick(n: Notification): Promise<void> {
  if (!n.is_read) await markAsRead(n.id)
  isOpen.value = false
  router.push((n.data.url as string) || '/notifications')
}

async function onMarkAll(): Promise<void> {
  if (unreadCount.value === 0) return
  try { await markAllAsRead() } catch { toast.error('Failed to mark all as read') }
}

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const m = Math.floor(diff / 60000), h = Math.floor(diff / 3600000), d = Math.floor(diff / 86400000)
  if (m < 1) return 'Just now'
  if (m < 60) return `${m}m ago`
  if (h < 24) return `${h}h ago`
  if (d < 7) return `${d}d ago`
  return new Date(dateStr).toLocaleDateString()
}

onMounted(() => {
  fetchUnreadCount()
  subscribeToRealtime()
})
watch(isOpen, (open) => { if (!open) fetchUnreadCount() })
</script>

<template>
  <div class="relative">
    <button
      type="button"
      class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-zinc-800"
      title="Notifications"
      @click="toggle"
    >
      <AppIcon name="bell" :size="18" />
      <span
        v-if="unreadCount > 0"
        class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 z-50 mt-2 w-[22rem] origin-top-right overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
      >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-zinc-700">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
          <button
            v-if="unreadCount > 0"
            type="button"
            class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 text-xs font-medium text-cyan-700 transition-colors hover:bg-cyan-50 dark:text-cyan-400 dark:hover:bg-cyan-950/30"
            @click="onMarkAll"
          >
            <AppIcon name="check" :size="14" />
            Mark all read
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="flex justify-center py-8">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-cyan-600 border-t-transparent" />
          </div>

          <div v-else-if="displayed.length === 0" class="px-4 py-10 text-center">
            <div class="mx-auto grid size-12 place-items-center rounded-full bg-gray-100 dark:bg-zinc-800">
              <AppIcon name="bell" class="text-gray-400" :size="22" />
            </div>
            <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-200">You're all caught up</p>
            <p class="mt-1 text-xs text-gray-500">New activity will appear here.</p>
          </div>

          <ul v-else class="divide-y divide-gray-100 dark:divide-zinc-800">
            <li v-for="n in displayed" :key="n.id">
              <button
                type="button"
                class="relative flex w-full items-start gap-3 px-4 py-3 text-left transition-colors"
                :class="!n.is_read ? 'bg-cyan-50/50 hover:bg-cyan-50 dark:bg-cyan-950/10 dark:hover:bg-cyan-950/20' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50'"
                @click="onClick(n)"
              >
                <span v-if="!n.is_read" class="absolute inset-y-0 left-0 w-0.5 bg-cyan-500" aria-hidden="true" />
                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg" :class="meta(n.type).tone">
                  <AppIcon :name="meta(n.type).icon" :size="16" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-start justify-between gap-2">
                    <p class="truncate text-sm" :class="!n.is_read ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-200'">
                      {{ n.data.title ?? 'Notification' }}
                    </p>
                    <span class="shrink-0 text-[11px] text-gray-400">{{ timeAgo(n.created_at) }}</span>
                  </div>
                  <p v-if="n.data.body" class="mt-0.5 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ n.data.body }}</p>
                </div>
              </button>
            </li>
          </ul>
        </div>

        <div class="border-t border-gray-200 dark:border-zinc-700">
          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 px-4 py-3 text-sm font-medium text-cyan-700 transition-colors hover:bg-gray-50 dark:text-cyan-400 dark:hover:bg-zinc-800"
            @click="isOpen = false; router.push('/notifications')"
          >
            See all notifications
            <AppIcon name="arrow-right" :size="16" />
          </button>
        </div>
      </div>
    </Transition>

    <div v-if="isOpen" class="fixed inset-0 z-40" @click="isOpen = false" />
  </div>
</template>
