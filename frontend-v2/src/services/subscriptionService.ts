import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { StorePlan } from '@/types/printly'

/** A plan definition from the catalogue (config/plans.php). */
export interface Plan {
  code: StorePlan
  name: string
  tagline: string
  price_cents: number
  features: string[]
  limits: Record<string, number | null>
}

export type SubscriptionStatus = 'trialing' | 'active' | 'past_due' | 'canceled'

export interface Subscription {
  plan: StorePlan
  plan_name: string
  status: SubscriptionStatus
  price_cents: number
  trial_ends_at: string | null
  current_period_end: string | null
  canceled_at: string | null
  /** Feature keys the store is entitled to right now (respects a lapsed sub). */
  features: string[]
  limits: Record<string, number | null>
  /** The full upgrade ladder for the comparison grid. */
  plans: Plan[]
  /** Whether the current user may change the plan (subscriptions.manage). */
  can_manage: boolean
}

const url = (storeId: string) => `/api/v1/stores/${storeId}/subscription`

export async function getSubscription(storeId: string): Promise<Subscription> {
  const res = await api.get<ApiResponse<Subscription>>(url(storeId))
  return res.data.data
}

export async function changePlan(storeId: string, plan: StorePlan): Promise<Subscription> {
  const res = await api.put<ApiResponse<Subscription>>(url(storeId), { plan })
  return res.data.data
}
