import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermission() {
  const authStore = useAuthStore()

  const isAdmin = computed(() => authStore.isAdmin)
  const roleLevel = computed(() => authStore.roleLevel)

  function can(permission: string): boolean {
    return authStore.can(permission)
  }

  function canAny(permissions: string[]): boolean {
    return authStore.canAny(permissions)
  }

  function canAll(permissions: string[]): boolean {
    return authStore.canAll(permissions)
  }

  function hasRole(role: string): boolean {
    return authStore.hasRole(role)
  }

  return {
    can,
    canAny,
    canAll,
    hasRole,
    isAdmin,
    roleLevel,
  }
}
