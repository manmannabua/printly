import api from '@/services/api'
import type { ApiResponse } from '@/types/api'

export interface PaymentSettings {
  payments_enabled: boolean
  accepts_online: boolean
  has_secret_key: boolean
  has_webhook_secret: boolean
  webhook_url: string
}

export interface PaymentSettingsPayload {
  payments_enabled: boolean
  // Write-only — omit or leave blank to keep the existing key.
  paymongo_secret_key?: string
  paymongo_webhook_secret?: string
}

const url = (storeId: string) => `/api/v1/stores/${storeId}/payment-settings`

export async function getPaymentSettings(storeId: string): Promise<PaymentSettings> {
  const res = await api.get<ApiResponse<PaymentSettings>>(url(storeId))
  return res.data.data
}

export async function updatePaymentSettings(storeId: string, payload: PaymentSettingsPayload): Promise<PaymentSettings> {
  const res = await api.put<ApiResponse<PaymentSettings>>(url(storeId), payload)
  return res.data.data
}

export async function disconnectPayments(storeId: string): Promise<PaymentSettings> {
  const res = await api.delete<ApiResponse<PaymentSettings>>(url(storeId))
  return res.data.data
}
