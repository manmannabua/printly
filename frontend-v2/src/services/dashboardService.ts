import api from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { DashboardData } from '@/types/printly'

export async function getDashboard(): Promise<DashboardData> {
  const res = await api.get<ApiResponse<DashboardData>>('/api/v1/dashboard')
  return res.data.data
}
