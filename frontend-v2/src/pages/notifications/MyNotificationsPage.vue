<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import { Card } from '@/components/ui/card'
import { useNotifications } from '@/composables/useNotifications'
import type { Notification } from '@/types/notification'

const router = useRouter()
const {
  notifications,
  unreadCount,
  loading,
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteReadNotifications,
} = useNotifications()

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const m = Math.floor(diff / 60000), h = Math.floor(diff / 3600000), d = Math.floor(diff / 86400000)
  if (m < 1) return 'Just now'
  if (m < 60) return `${m}m ago`
  if (h < 24) return `${h}h ago`
  if (d < 7) return `${d}d ago`
  return new Date(dateStr).toLocaleDateString()
}

async function onClick(n: Notification): Promise<void> {
  if (!n.is_read) await markAsRead(n.id)
  if (n.data.url) router.push(n.data.url as string)
}

onMounted(() => fetchNotifications({ per_page: 50 }))
</script>

<template>
  <div>
    <AppPageHeader title="Notifications" subtitle="Everything that needs your eyes">
      <template #actions>
        <div class="flex gap-2">
          <button
            v-if="unreadCount > 0"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-200 dark:hover:bg-zinc-800"
            @click="markAllAsRead"
          >
            <AppIcon name="check" :size="15" /> Mark all read
          </button>
          <button
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-200 dark:hover:bg-zinc-800"
            @click="deleteReadNotifications"
          >
            <AppIcon name="trash" :size="15" /> Clear read
          </button>
        </div>
      </template>
    </AppPageHeader>

    <Card class="overflow-hidden p-0">
      <div v-if="loading" class="flex justify-center py-16">
        <div class="h-7 w-7 animate-spin rounded-full border-2 border-cyan-600 border-t-transparent" />
      </div>

      <div v-else-if="notifications.length === 0" class="flex flex-col items-center gap-2 py-20 text-center">
        <div class="grid size-14 place-items-center rounded-full bg-gray-100 dark:bg-zinc-800">
          <AppIcon name="bell" :size="26" class="text-gray-400" />
        </div>
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">No notifications yet</p>
        <p class="text-xs text-gray-500">New activity will show up here.</p>
      </div>

      <ul v-else class="divide-y divide-gray-100 dark:divide-zinc-800">
        <li v-for="n in notifications" :key="n.id">
          <button
            type="button"
            class="flex w-full items-start gap-3 px-4 py-4 text-left transition-colors"
            :class="!n.is_read ? 'bg-cyan-50/40 hover:bg-cyan-50 dark:bg-cyan-950/10 dark:hover:bg-cyan-950/20' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/50'"
            @click="onClick(n)"
          >
            <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="n.is_read ? 'bg-transparent' : 'bg-cyan-500'" />
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-2">
                <p class="text-sm" :class="!n.is_read ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-200'">
                  {{ n.data.title ?? 'Notification' }}
                </p>
                <span class="shrink-0 text-xs text-gray-400">{{ timeAgo(n.created_at) }}</span>
              </div>
              <p v-if="n.data.body" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ n.data.body }}</p>
            </div>
          </button>
        </li>
      </ul>
    </Card>
  </div>
</template>
