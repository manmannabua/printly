// Shared Printly types. Domain-specific types (stores, catalog, orders, etc.)
// are added per planning/01-data-model-and-architecture.md.

export type Upload = {
  path: string
  url: string
  name: string
  size: number
}

/** Embedded 3D / video tour providers (reused by AppMultiUpload / tour embed util). */
export type TourProvider = 'matterport' | 'youtube' | 'vimeo' | 'kuula'

/** High-level counts shown on the admin dashboard (skeleton). */
export interface DashboardCounts {
  users: number
  active_users: number
}

// ── Stores & catalog ─────────────────────────────────────────────────────────

export type StorePlan = 'starter' | 'pro' | 'auto'
export type StoreStatus = 'active' | 'suspended' | 'trial'

export interface Store {
  id: string
  name: string
  slug: string
  plan: StorePlan
  status: StoreStatus
  timezone: string
  currency: string
  lat: number | null
  lng: number | null
  address: string | null
  settings: Record<string, unknown>
  product_types_count?: number
  products_count?: number
  created_at?: string
  updated_at?: string
}

export type PricingMode = 'file_based' | 'spec_based'
export type Fulfillment = 'manual' | 'auto'

export interface ProductType {
  id: string
  store_id: string
  name: string
  pricing_mode: PricingMode
  fulfillment: Fulfillment
  is_active: boolean
  sort_order: number
  products_count?: number
  created_at?: string
  updated_at?: string
}

export interface Product {
  id: string
  store_id: string
  product_type_id: string
  name: string
  base_price_cents: number
  is_active: boolean
  sort_order: number
  product_type?: ProductType
  price_rules?: PriceRule[]
  price_rules_count?: number
  created_at?: string
  updated_at?: string
}

export type ModifierType = 'per_page' | 'per_job' | 'multiplier'

export interface PriceRule {
  id: string
  store_id: string
  product_id: string
  attribute: string
  match_value: string
  modifier_type: ModifierType
  amount_cents: number | null
  multiplier: number | null
  created_at?: string
  updated_at?: string
}

export interface QuoteBreakdownLine {
  label: string
  type: string
  amount_cents?: number
  multiplier?: number
}

export interface Quote {
  product_id: string
  pricing_mode: PricingMode
  total_cents: number
  copies?: number
  quantity?: number
  breakdown: QuoteBreakdownLine[]
}
