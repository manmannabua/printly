import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Order, OrderStatus } from '@/types/printly'

const base = (storeId: string) => `/api/v1/stores/${storeId}`

export async function getOrder(storeId: string, orderId: string): Promise<Order> {
  const res = await api.get<ApiResponse<Order>>(`${base(storeId)}/orders/${orderId}`)
  return res.data.data
}

export async function transitionOrder(
  storeId: string,
  orderId: string,
  status: OrderStatus,
  reason?: string,
): Promise<Order> {
  const res = await api.patch<ApiResponse<Order>>(`${base(storeId)}/orders/${orderId}`, { status, reason })
  return res.data.data
}

/** Absolute URL to stream a print file (private disk, member-only). */
export function fileDownloadUrl(storeId: string, fileId: string): string {
  const root = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')
  return `${root}${base(storeId)}/files/${fileId}/download`
}
