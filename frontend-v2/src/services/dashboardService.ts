import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { DashboardCounts } from '@/types/printly'

export async function getDashboard(): Promise<DashboardCounts> {
  const res = await api.get<ApiResponse<DashboardCounts>>('/api/v1/dashboard')
  return res.data.data
}
