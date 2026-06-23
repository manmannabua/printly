import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Notification } from '@/types/notification'

export async function getNotifications(
  params?: Record<string, unknown>,
): Promise<{ data: Notification[], meta: Record<string, unknown> }> {
  const response = await api.get<{ data: Notification[], meta: Record<string, unknown> }>(
    '/api/v1/my/notifications',
    { params },
  )
  return response.data
}

export async function getUnreadCount(): Promise<number> {
  const response = await api.get<ApiResponse<{ unread_count: number }>>('/api/v1/my/notifications/unread-count')
  return response.data.data.unread_count
}

export async function markAsRead(id: string): Promise<Notification> {
  const response = await api.post<ApiResponse<Notification>>(`/api/v1/my/notifications/${id}/read`)
  return response.data.data
}

export async function markAllAsRead(): Promise<void> {
  await api.post('/api/v1/my/notifications/read-all')
}

export async function deleteNotification(id: string): Promise<void> {
  await api.delete(`/api/v1/my/notifications/${id}`)
}

export async function deleteReadNotifications(): Promise<void> {
  await api.delete('/api/v1/my/notifications/read')
}
