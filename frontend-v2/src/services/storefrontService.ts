import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { ChatConversation, ChatMessage } from '@/types/chat'
import type {
  OrderFile,
  PlacedOrder,
  PublicOrder,
  Quote,
  StorefrontCatalog,
} from '@/types/printly'

// Public, guest-facing storefront API (planning §7). No auth — slug-scoped.

export async function getStorefront(slug: string): Promise<StorefrontCatalog> {
  const res = await api.get<ApiResponse<StorefrontCatalog>>(`/api/v1/s/${slug}`)
  return res.data.data
}

export async function uploadStorefrontFile(slug: string, file: File): Promise<OrderFile> {
  const form = new FormData()
  form.append('file', file)
  const res = await api.post<ApiResponse<OrderFile>>(`/api/v1/s/${slug}/files`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return res.data.data
}

export async function getStorefrontFile(slug: string, id: string): Promise<OrderFile> {
  const res = await api.get<ApiResponse<OrderFile>>(`/api/v1/s/${slug}/files/${id}`)
  return res.data.data
}

export type StorefrontQuotePayload = {
  product_id: string
  // file_based
  page_count?: number
  paper_size?: string
  color?: 'color' | 'bw'
  duplex?: boolean
  copies?: number
  // spec_based
  selections?: { option_id: string, choice: string }[]
  quantity?: number
}

export async function quoteStorefront(slug: string, payload: StorefrontQuotePayload): Promise<Quote> {
  const res = await api.post<ApiResponse<Quote>>(`/api/v1/s/${slug}/quote`, payload)
  return res.data.data
}

export type PlaceOrderItem = {
  product_id: string
  quantity?: number
  file_ids?: string[]
  spec?: {
    page_count?: number
    paper_size?: string
    color?: 'color' | 'bw'
    duplex?: boolean
    copies?: number
  }
  selections?: { option_id: string, choice: string }[]
}

export type PlaceOrderPayload = {
  customer: { name?: string, phone?: string, email?: string }
  pay_method?: string
  notes?: string
  items: PlaceOrderItem[]
}

export async function placeStorefrontOrder(slug: string, payload: PlaceOrderPayload): Promise<PlacedOrder> {
  const res = await api.post<ApiResponse<PlacedOrder>>(`/api/v1/s/${slug}/orders`, payload)
  return res.data.data
}

export async function getPublicOrder(code: string): Promise<PublicOrder> {
  const res = await api.get<ApiResponse<PublicOrder>>(`/api/v1/orders/${code}`)
  return res.data.data
}

// ── Per-order customer chat (public, code-scoped) ────────────────────────────

export async function getOrderChat(code: string): Promise<{ conversation: ChatConversation, messages: ChatMessage[] }> {
  const res = await api.get<ApiResponse<{ conversation: ChatConversation, messages: ChatMessage[] }>>(`/api/v1/orders/${code}/chat`)
  return res.data.data
}

export async function sendOrderChat(code: string, body: string): Promise<ChatMessage> {
  const res = await api.post<ApiResponse<ChatMessage>>(`/api/v1/orders/${code}/chat/messages`, { body })
  return res.data.data
}

export async function markOrderChatRead(code: string, messageId: string): Promise<void> {
  await api.post(`/api/v1/orders/${code}/chat/read`, { message_id: messageId })
}

export async function reactOrderChat(code: string, messageId: string, emoji: string): Promise<void> {
  await api.post(`/api/v1/orders/${code}/chat/messages/${messageId}/reactions`, { emoji })
}

export async function sendOrderTyping(code: string, isTyping: boolean): Promise<void> {
  await api.post(`/api/v1/orders/${code}/chat/typing`, { is_typing: isTyping })
}
