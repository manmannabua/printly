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

// ── Storefront (public, guest-facing) ──────────────────────────────────────────

export interface ProductOptionChoice {
  label: string
  price_delta_cents?: number
}

export interface ProductOption {
  id: string
  name: string
  choices: ProductOptionChoice[]
}

export interface StorefrontPriceRule {
  attribute: string
  match_value: string
  modifier_type: ModifierType
  amount_cents: number | null
  multiplier: number | null
}

export interface StorefrontProduct {
  id: string
  name: string
  pricing_mode: PricingMode
  base_price_cents: number
  price_rules: StorefrontPriceRule[]
  options: ProductOption[]
}

export interface StorefrontProductType {
  id: string
  name: string
  pricing_mode: PricingMode
  fulfillment: Fulfillment
  products: StorefrontProduct[]
}

export interface StorefrontStore {
  name: string
  slug: string
  currency: string | null
  timezone: string | null
  address: string | null
  settings: {
    accepts_guest: boolean
    pay_on_pickup_allowed: boolean
  }
}

export interface StorefrontCatalog {
  store: StorefrontStore
  product_types: StorefrontProductType[]
}

export type AnalysisStatus = 'pending' | 'done' | 'failed'

export interface OrderFile {
  id: string
  store_id: string
  order_item_id: string | null
  original_name: string
  mime: string | null
  size_bytes: number
  page_count: number | null
  paper_size: string | null
  is_color: boolean | null
  analysis_status: AnalysisStatus
  analysis_error: string | null
  created_at?: string
}

export type OrderStatus =
  | 'draft' | 'pending_payment' | 'paid' | 'accepted' | 'in_progress'
  | 'ready' | 'completed' | 'cancelled' | 'rejected' | 'failed' | 'refunded'

export type PayMethod = 'gcash' | 'card' | 'maya' | 'cash_on_pickup'

export interface PlacedOrder {
  code: string
  status: OrderStatus
  payment_status: string
  total_cents: number
  checkout_url?: string | null
}

export interface PublicOrder {
  code: string
  status: OrderStatus
  payment_status: string
  total_cents: number
  store: { name: string | null, slug: string | null }
  placed_at: string | null
  ready_at: string | null
  completed_at: string | null
}

// ── Orders (staff dashboard) ───────────────────────────────────────────────────

export interface OrderItem {
  id: string
  product_id: string | null
  product_name: string
  pricing_mode: PricingMode
  quantity: number
  unit_breakdown: QuoteBreakdownLine[] | null
  spec_selections: unknown
  line_total_cents: number
  files?: OrderFile[]
}

export interface OrderEvent {
  id: string
  from_status: OrderStatus | null
  to_status: OrderStatus
  actor_type: 'system' | 'staff' | 'customer'
  actor_id: string | null
  meta: Record<string, unknown> | null
  created_at?: string
}

export interface Order {
  id: string
  store_id: string
  customer_id: string | null
  code: string
  status: OrderStatus
  payment_status: string
  pay_method: PayMethod | null
  subtotal_cents: number
  fee_cents: number
  total_cents: number
  notes: string | null
  placed_at: string | null
  accepted_at: string | null
  ready_at: string | null
  completed_at: string | null
  items?: OrderItem[]
  events?: OrderEvent[]
  created_at?: string
}
