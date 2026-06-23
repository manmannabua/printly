import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Store } from '@/types/printly'

export type StorePayload = Partial<Omit<Store, 'id' | 'created_at' | 'updated_at' | 'product_types_count' | 'products_count'>>

export async function getStore(id: string): Promise<Store> {
  const res = await api.get<ApiResponse<Store>>(`/api/v1/stores/${id}`)
  return res.data.data
}

export async function createStore(payload: StorePayload): Promise<Store> {
  const res = await api.post<ApiResponse<Store>>('/api/v1/stores', payload)
  return res.data.data
}

export async function updateStore(id: string, payload: StorePayload): Promise<Store> {
  const res = await api.put<ApiResponse<Store>>(`/api/v1/stores/${id}`, payload)
  return res.data.data
}

export async function deleteStore(id: string): Promise<void> {
  await api.delete(`/api/v1/stores/${id}`)
}
