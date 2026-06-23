import axios from 'axios'
import type { AxiosError, AxiosResponse } from 'axios'
import type { ApiResponse, ApiError } from '@/types/api'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Attach the public buyer's Bearer token to buyer-scoped requests only, so the
// buyer session never leaks into the admin (cookie-session) endpoints.
api.interceptors.request.use((config) => {
  if (config.url?.includes('/api/v1/buyer/')) {
    const token = localStorage.getItem('buyer_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
  }
  return config
})

let isRedirectingToLogin = false

api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError<ApiError>) => {
    const status = error.response?.status

    // Pages that must remain accessible even when unauthenticated — don't redirect
    const authFreeRoutes = ['/reset-password', '/forgot-password', '/mobile-clock-in', '/public-exam', '/sign/', '/dev/', '/browse', '/account']

    if (status === 401 && !isRedirectingToLogin) {
      const { useAuthStore } = await import('@/stores/auth')
      const authStore = useAuthStore()

      // If the lock screen is showing, don't redirect to login — the lock screen
      // handles session expiry by showing an error on PIN verify failure.
      if (authStore.isLocked) {
        return Promise.reject(error)
      }

      isRedirectingToLogin = true
      authStore.$reset()

      const { default: router } = await import('@/router')
      const currentPath = router.currentRoute.value.fullPath
      const actualPath = window.location.pathname

      if (currentPath !== '/login' && !authFreeRoutes.some(r => actualPath.startsWith(r))) {
        await router.push({
          path: '/login',
          query: { redirect: currentPath, expired: '1' },
        })
      }

      setTimeout(() => {
        isRedirectingToLogin = false
      }, 1000)
    }

    return Promise.reject(error)
  },
)

export async function ensureCsrfCookie(): Promise<void> {
  await api.get('/sanctum/csrf-cookie')
}

export function isApiValidationError(
  error: unknown,
): error is AxiosError<ApiError> {
  return axios.isAxiosError(error) && error.response?.status === 422
}

export function getValidationErrors(
  error: unknown,
): Record<string, string[]> {
  if (isApiValidationError(error)) {
    return error.response?.data?.errors ?? {}
  }
  return {}
}

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as ApiError | undefined
    if (data?.message) return data.message

    if (error.response?.status === 403) return 'You do not have permission to perform this action.'
    if (error.response?.status === 404) return 'The requested resource was not found.'
    if (error.response?.status === 429) return 'Too many requests. Please try again later.'
    if ((error.response?.status ?? 0) >= 500) return 'A server error occurred. Please try again later.'
    if (!error.response) return 'Unable to connect to the server. Please check your connection.'
  }

  return 'An unexpected error occurred.'
}

export type { ApiResponse }
export default api
