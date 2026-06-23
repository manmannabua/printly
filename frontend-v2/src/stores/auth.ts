import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/auth'
import * as authService from '@/services/authService'

const USER_CACHE_KEY = 'hris_cached_user'
const LOCK_KEY = 'hris_session_locked'
const AUTH_CHANNEL_NAME = 'hris_auth'

type AuthBroadcastMessage = { type: 'lock' } | { type: 'unlock' } | { type: 'logout' }

function persistLock(): void {
  try { sessionStorage.setItem(LOCK_KEY, '1') } catch { /* storage full */ }
}

function clearLock(): void {
  try { sessionStorage.removeItem(LOCK_KEY) } catch { /* ignore */ }
}

function isLockPersisted(): boolean {
  try { return sessionStorage.getItem(LOCK_KEY) === '1' } catch { return false }
}

let authChannel: BroadcastChannel | null = null
function getAuthChannel(): BroadcastChannel | null {
  if (authChannel) return authChannel
  if (typeof BroadcastChannel === 'undefined') return null
  try {
    authChannel = new BroadcastChannel(AUTH_CHANNEL_NAME)
    return authChannel
  } catch { return null }
}

function cacheUser(u: User): void {
  // Cache only minimal data for offline sessions — strip email, employee_number, full role objects, etc.
  const minimal = {
    id: u.id,
    is_active: u.is_active,
    primary_role: u.primary_role,
    role_level: u.role_level,
    is_admin: u.is_admin,
    is_team_leader: u.is_team_leader,
    led_team_ids: u.led_team_ids,
    has_security_pin: u.has_security_pin,
    roles: u.roles?.map((r) => ({ name: r.name })),
    permissions: u.permissions, // needed for offline can() checks
    employee: u.employee
      ? {
          id: u.employee.id,
          first_name: u.employee.first_name,
          last_name: u.employee.last_name,
          profile_photo_url: u.employee.profile_photo_url,
          status: u.employee.status,
        }
      : null,
  }
  try { localStorage.setItem(USER_CACHE_KEY, JSON.stringify(minimal)) } catch { /* storage full */ }
}

function getCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_CACHE_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch { return null }
}

function clearCachedUser(): void {
  localStorage.removeItem(USER_CACHE_KEY)
}

function isNetworkError(err: unknown): boolean {
  const e = err as { response?: unknown; code?: string }
  return !e?.response || e?.code === 'ERR_NETWORK'
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const initialized = ref(false)
  const loading = ref(false)
  const isOfflineSession = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isLocked = ref(false)
  const permissions = computed(() => user.value?.permissions ?? [])
  const primaryRole = computed(() => user.value?.primary_role ?? null)
  const departmentRole = computed(() => user.value?.department_role ?? null)
  const roleLevel = computed(() => user.value?.role_level ?? 999)
  const isAdmin = computed(() => user.value?.is_admin ?? false)
  const employee = computed(() => user.value?.employee ?? null)
  const hasEmployee = computed(() => !!user.value?.employee)
  const isTeamLeader = computed(() => user.value?.is_team_leader ?? false)
  const ledTeamIds = computed(() => user.value?.led_team_ids ?? [])
  const hasSecurityPin = computed(() => user.value?.has_security_pin ?? false)

  function can(permission: string): boolean {
    if (isAdmin.value) return true
    if (permissions.value.includes(permission)) return true
    // Team leaders implicitly hold all .team-scoped permissions (mirrors backend isCurrentTeamLeader() fallback)
    if (isTeamLeader.value && permission.endsWith('.team')) return true
    return false
  }

  function canAny(perms: string[]): boolean {
    if (isAdmin.value) return true
    if (perms.some((p) => permissions.value.includes(p))) return true
    // Team leaders implicitly hold all .team-scoped permissions
    if (isTeamLeader.value && perms.some((p) => p.endsWith('.team'))) return true
    return false
  }

  function canAll(perms: string[]): boolean {
    if (isAdmin.value) return true
    return perms.every((p) => permissions.value.includes(p))
  }

  function hasRole(role: string): boolean {
    if (!user.value?.roles) return false
    return user.value.roles.some((r) => r.name === role)
  }

  async function init(): Promise<void> {
    if (initialized.value) return
    loading.value = true
    try {
      const u = await authService.getUser()
      user.value = u
      isOfflineSession.value = false
      cacheUser(u)
      // Server-side session lock takes precedence — a new tab sharing the
      // same session cookie must inherit the locked state. Fall back to the
      // tab-local sessionStorage flag if the server didn't return one.
      if (u.is_locked === true || isLockPersisted()) {
        isLocked.value = true
        persistLock()
      }
    } catch (err: unknown) {
      if (isNetworkError(err)) {
        // Offline — restore from localStorage cache instead of logging out
        const cached = getCachedUser()
        if (cached) {
          user.value = cached
          isOfflineSession.value = true
        } else {
          user.value = null
        }
      } else {
        // Real auth failure (401) — clear everything
        user.value = null
        clearCachedUser()
      }
    } finally {
      initialized.value = true
      loading.value = false
    }
  }

  async function login(email: string, password: string): Promise<void> {
    loading.value = true
    try {
      const u = await authService.login({ email, password })
      user.value = u
      isOfflineSession.value = false
      initialized.value = true
      cacheUser(u)
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    loading.value = true
    isLocked.value = false
    clearLock()
    try {
      await authService.logout()
    } catch {
      // Server-side logout can fail when the session is already gone (401)
      // or the network is unreachable. Local state is still cleared below,
      // so the user ends up logged out either way.
    } finally {
      user.value = null
      isOfflineSession.value = false
      initialized.value = true
      loading.value = false
      clearCachedUser()
      broadcast({ type: 'logout' })
    }
  }

  function lock(): void {
    if (isAuthenticated.value) {
      isLocked.value = true
      persistLock()
      broadcast({ type: 'lock' })
      // Persist the lock server-side so newly opened tabs (which share the
      // same session cookie) inherit the locked state on load.
      authService.lockSession().catch(() => { /* fire-and-forget */ })
    }
  }

  function unlock(): void {
    isLocked.value = false
    clearLock()
    broadcast({ type: 'unlock' })
  }

  function broadcast(msg: AuthBroadcastMessage): void {
    const ch = getAuthChannel()
    if (!ch) return
    try { ch.postMessage(msg) } catch { /* channel closed */ }
  }

  function handleBroadcast(msg: AuthBroadcastMessage): void {
    if (msg.type === 'lock') {
      if (isAuthenticated.value) {
        isLocked.value = true
        persistLock()
      }
    } else if (msg.type === 'unlock') {
      isLocked.value = false
      clearLock()
    } else if (msg.type === 'logout') {
      user.value = null
      isLocked.value = false
      clearLock()
      clearCachedUser()
      isOfflineSession.value = false
      initialized.value = true
    }
  }

  const ch = getAuthChannel()
  if (ch) {
    ch.addEventListener('message', (e: MessageEvent<AuthBroadcastMessage>) => {
      handleBroadcast(e.data)
    })
  }

  function $reset(): void {
    user.value = null
    initialized.value = true
    loading.value = false
    isLocked.value = false
    clearLock()
    isOfflineSession.value = false
    clearCachedUser()
  }

  return {
    user,
    initialized,
    loading,
    isAuthenticated,
    isLocked,
    isOfflineSession,
    permissions,
    primaryRole,
    departmentRole,
    roleLevel,
    isAdmin,
    employee,
    hasEmployee,
    isTeamLeader,
    ledTeamIds,
    hasSecurityPin,
    can,
    canAny,
    canAll,
    hasRole,
    init,
    login,
    logout,
    lock,
    unlock,
    $reset,
  }
})
