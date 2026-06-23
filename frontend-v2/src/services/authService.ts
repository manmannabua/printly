import api, { ensureCsrfCookie } from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { User, LoginRequest, ForgotPasswordRequest, ResetPasswordRequest } from '@/types/auth'

export async function login(credentials: LoginRequest): Promise<User> {
  await ensureCsrfCookie()
  const response = await api.post<ApiResponse<User>>('/api/v1/login', credentials)
  return response.data.data
}

export async function logout(): Promise<void> {
  await api.post('/api/v1/auth/logout')
}

export async function getUser(): Promise<User> {
  const response = await api.get<ApiResponse<User>>('/api/v1/auth/me')
  return response.data.data
}

export async function lockSession(): Promise<void> {
  await api.post('/api/v1/auth/lock')
}

export async function forgotPassword(data: ForgotPasswordRequest): Promise<string> {
  const response = await api.post<ApiResponse<null>>('/api/v1/auth/forgot-password', data)
  return response.data.message
}

export async function resetPassword(data: ResetPasswordRequest): Promise<string> {
  const response = await api.post<ApiResponse<null>>('/api/v1/auth/reset-password', data)
  return response.data.message
}
