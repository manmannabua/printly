export type ChatSide = 'admin' | 'store' | 'customer'

export interface ChatReactionSummary {
  emoji: string
  count: number
  user_ids: string[]
}

export interface ChatMessage {
  id: string
  conversation_id: string
  sender_user_id: string | null
  sender_side: ChatSide
  sender_name: string | null
  body: string | null
  type: string
  reply_to_id: string | null
  is_deleted: boolean
  edited_at: string | null
  created_at: string
  reactions: ChatReactionSummary[]
}

export interface ChatConversation {
  id: string
  type: 'internal' | 'order'
  store_id: string | null
  order_id: string | null
  order_code: string | null
  title: string
  last_message_at: string | null
  last_message_preview: string | null
  unread_count: number
  my_side: ChatSide
}
