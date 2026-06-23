export interface NotificationData {
  title?: string
  body?: string
  url?: string
  [key: string]: unknown
}

export interface Notification {
  id: string
  type: string
  data: NotificationData
  read_at: string | null
  is_read: boolean
  created_at: string
}
