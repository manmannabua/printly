import { ref, computed, onMounted, onUnmounted, type ComputedRef } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useReverbChannel } from '@/composables/useReverbChannel'
import * as notificationService from '@/services/notificationService'
import type { Notification } from '@/types/notification'

export interface UseNotificationsReturn {
  notifications: ComputedRef<Notification[]>
  unreadCount: ComputedRef<number>
  loading: ComputedRef<boolean>
  fetchNotifications: (params?: Record<string, unknown>) => Promise<void>
  fetchUnreadCount: () => Promise<void>
  markAsRead: (id: string) => Promise<void>
  markAllAsRead: () => Promise<void>
  deleteNotification: (id: string) => Promise<void>
  deleteReadNotifications: () => Promise<void>
  subscribeToRealtime: () => void
  unsubscribeFromRealtime: () => void
}

export interface UseNotificationsOptions {
  onNotificationReceived?: (notification: Notification) => void
}

// Module-level shared count so the bell badge and the notifications page agree.
const sharedUnreadCount = ref(0)

export function useNotifications(options?: UseNotificationsOptions): UseNotificationsReturn {
  const authStore = useAuthStore()
  const reverb = useReverbChannel()

  const notifications = ref<Notification[]>([])
  const loading = ref(false)
  let realtimeSubscribed = false

  async function fetchNotifications(params?: Record<string, unknown>): Promise<void> {
    loading.value = true
    try {
      const response = await notificationService.getNotifications(params)
      notifications.value = response.data
    } catch (e) {
      console.error('Failed to fetch notifications:', e)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnreadCount(): Promise<void> {
    try {
      sharedUnreadCount.value = await notificationService.getUnreadCount()
    } catch (e) {
      console.error('Failed to fetch unread count:', e)
    }
  }

  async function markAsRead(id: string): Promise<void> {
    const updated = await notificationService.markAsRead(id)
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) notifications.value[index] = updated
    if (sharedUnreadCount.value > 0) sharedUnreadCount.value--
  }

  async function markAllAsRead(): Promise<void> {
    await notificationService.markAllAsRead()
    notifications.value = notifications.value.map(n => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
    sharedUnreadCount.value = 0
  }

  async function deleteNotification(id: string): Promise<void> {
    await notificationService.deleteNotification(id)
    const n = notifications.value.find(x => x.id === id)
    if (n && !n.is_read && sharedUnreadCount.value > 0) sharedUnreadCount.value--
    notifications.value = notifications.value.filter(x => x.id !== id)
  }

  async function deleteReadNotifications(): Promise<void> {
    await notificationService.deleteReadNotifications()
    notifications.value = notifications.value.filter(n => !n.is_read)
  }

  function handleCreated(data: Notification): void {
    notifications.value.unshift(data)
    sharedUnreadCount.value++
    options?.onNotificationReceived?.(data)
  }

  function handleRead(data: { notification_id: string, read_at: string }): void {
    const index = notifications.value.findIndex(n => n.id === data.notification_id)
    const existing = index !== -1 ? notifications.value[index] : undefined
    if (existing && !existing.is_read) {
      notifications.value[index] = { ...existing, is_read: true, read_at: data.read_at }
      if (sharedUnreadCount.value > 0) sharedUnreadCount.value--
    }
  }

  function subscribeToRealtime(): void {
    if (realtimeSubscribed || !authStore.user?.id) return
    const channel = `App.Models.User.${authStore.user.id}`
    reverb.subscribeToPrivate<Notification>(channel, '.notification.created', handleCreated)
    reverb.subscribeToPrivate<{ notification_id: string, read_at: string }>(channel, '.notification.read', handleRead)
    realtimeSubscribed = true
  }

  function unsubscribeFromRealtime(): void {
    if (!realtimeSubscribed) return
    reverb.unsubscribeAll()
    realtimeSubscribed = false
  }

  onMounted(fetchUnreadCount)
  onUnmounted(unsubscribeFromRealtime)

  return {
    notifications: computed(() => notifications.value),
    unreadCount: computed(() => sharedUnreadCount.value),
    loading: computed(() => loading.value),
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    deleteReadNotifications,
    subscribeToRealtime,
    unsubscribeFromRealtime,
  }
}
