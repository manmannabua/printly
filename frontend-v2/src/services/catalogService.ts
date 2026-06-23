import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { PriceRule, Product, ProductType, Quote } from '@/types/printly'

// ── Product types ────────────────────────────────────────────────────────────
export type ProductTypePayload = {
  name: string
  pricing_mode: 'file_based' | 'spec_based'
  fulfillment?: 'manual' | 'auto'
  is_active?: boolean
  sort_order?: number
}

export async function listAllProductTypes(storeId: string): Promise<ProductType[]> {
  const res = await api.get<ApiResponse<ProductType[]>>(`/api/v1/stores/${storeId}/product-types`, { params: { all: true } })
  return res.data.data
}

export async function createProductType(storeId: string, payload: ProductTypePayload): Promise<ProductType> {
  const res = await api.post<ApiResponse<ProductType>>(`/api/v1/stores/${storeId}/product-types`, payload)
  return res.data.data
}

export async function updateProductType(storeId: string, id: string, payload: Partial<ProductTypePayload>): Promise<ProductType> {
  const res = await api.put<ApiResponse<ProductType>>(`/api/v1/stores/${storeId}/product-types/${id}`, payload)
  return res.data.data
}

export async function deleteProductType(storeId: string, id: string): Promise<void> {
  await api.delete(`/api/v1/stores/${storeId}/product-types/${id}`)
}

// ── Products ─────────────────────────────────────────────────────────────────
export type ProductPayload = {
  product_type_id: string
  name: string
  base_price_cents: number
  is_active?: boolean
  sort_order?: number
}

export async function createProduct(storeId: string, payload: ProductPayload): Promise<Product> {
  const res = await api.post<ApiResponse<Product>>(`/api/v1/stores/${storeId}/products`, payload)
  return res.data.data
}

export async function updateProduct(storeId: string, id: string, payload: Partial<ProductPayload>): Promise<Product> {
  const res = await api.put<ApiResponse<Product>>(`/api/v1/stores/${storeId}/products/${id}`, payload)
  return res.data.data
}

export async function deleteProduct(storeId: string, id: string): Promise<void> {
  await api.delete(`/api/v1/stores/${storeId}/products/${id}`)
}

// ── Price rules ──────────────────────────────────────────────────────────────
export type PriceRulePayload = {
  attribute: string
  match_value: string
  modifier_type: 'per_page' | 'per_job' | 'multiplier'
  amount_cents?: number | null
  multiplier?: number | null
}

export async function listPriceRules(storeId: string, productId: string): Promise<PriceRule[]> {
  const res = await api.get<ApiResponse<PriceRule[]>>(`/api/v1/stores/${storeId}/products/${productId}/price-rules`)
  return res.data.data
}

export async function createPriceRule(storeId: string, productId: string, payload: PriceRulePayload): Promise<PriceRule> {
  const res = await api.post<ApiResponse<PriceRule>>(`/api/v1/stores/${storeId}/products/${productId}/price-rules`, payload)
  return res.data.data
}

export async function deletePriceRule(storeId: string, productId: string, id: string): Promise<void> {
  await api.delete(`/api/v1/stores/${storeId}/products/${productId}/price-rules/${id}`)
}

// ── Quote (exercises PricingService) ─────────────────────────────────────────
export type FileQuoteSpec = {
  page_count: number
  paper_size?: string
  color?: 'color' | 'bw'
  duplex?: boolean
  copies?: number
}

export async function quoteProduct(storeId: string, productId: string, spec: Record<string, unknown>): Promise<Quote> {
  const res = await api.post<ApiResponse<Quote>>(`/api/v1/stores/${storeId}/products/${productId}/quote`, spec)
  return res.data.data
}
