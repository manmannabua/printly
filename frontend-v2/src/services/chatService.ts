import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { ChatConversation, ChatMessage } from '@/types/chat'

// Internal Admin↔Store chat (authenticated).

export async function getConversations(): Promise<ChatConversation[]> {
  const res = await api.get<ApiResponse<ChatConversation[]>>('/api/v1/chat/conversations')
  return res.data.data
}

export async function startInternal(storeId: string): Promise<ChatConversation> {
  const res = await api.post<ApiResponse<ChatConversation>>('/api/v1/chat/conversations', { store_id: storeId })
  return res.data.data
}

export async function getMessages(
  conversationId: string,
  params?: { before_id?: string, limit?: number },
): Promise<{ data: ChatMessage[], meta: { has_more: boolean } }> {
  const res = await api.get<{ data: ChatMessage[], meta: { has_more: boolean } }>(
    `/api/v1/chat/conversations/${conversationId}/messages`,
    { params },
  )
  return res.data
}

export async function sendMessage(conversationId: string, body: string, replyToId?: string | null): Promise<ChatMessage> {
  const res = await api.post<ApiResponse<ChatMessage>>(
    `/api/v1/chat/conversations/${conversationId}/messages`,
    { body, ...(replyToId ? { reply_to_id: replyToId } : {}) },
  )
  return res.data.data
}

export async function markRead(conversationId: string, messageId: string): Promise<void> {
  await api.post(`/api/v1/chat/conversations/${conversationId}/read`, { message_id: messageId })
}

export async function sendTyping(conversationId: string, isTyping: boolean): Promise<void> {
  await api.post(`/api/v1/chat/conversations/${conversationId}/typing`, { is_typing: isTyping })
}

export async function uploadAttachment(conversationId: string, file: File): Promise<ChatMessage> {
  const form = new FormData()
  form.append('file', file)
  const res = await api.post<ApiResponse<ChatMessage>>(
    `/api/v1/chat/conversations/${conversationId}/attachments`,
    form,
    { headers: { 'Content-Type': 'multipart/form-data' } },
  )
  return res.data.data
}

export async function editMessage(messageId: string, body: string): Promise<ChatMessage> {
  const res = await api.patch<ApiResponse<ChatMessage>>(`/api/v1/chat/messages/${messageId}`, { body })
  return res.data.data
}

export async function deleteMessage(messageId: string): Promise<void> {
  await api.delete(`/api/v1/chat/messages/${messageId}`)
}

export async function toggleReaction(messageId: string, emoji: string): Promise<void> {
  await api.post(`/api/v1/chat/messages/${messageId}/reactions`, { emoji })
}
