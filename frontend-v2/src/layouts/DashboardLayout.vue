<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDarkMode } from '@/composables/useDarkMode'
import { listStores } from '@/services/storeService'
import type { Store } from '@/types/printly'
import AppIcon from '@/components/common/AppIcon.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import NotificationBell from '@/components/notifications/NotificationBell.vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

interface NavItem {
  label: string
  to: string
  icon: string
  permission?: string
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { isDark, toggle: toggleDark } = useDarkMode()

const myStores = ref<Store[]>([])

onMounted(async () => {
  if (authStore.can('stores.view') || authStore.can('orders.view')) {
    try {
      myStores.value = await listStores()
    } catch {
      myStores.value = []
    }
  }
})

const activeStore = computed(() => myStores.value[0] ?? null)
const queueTo = computed(() => activeStore.value ? `/stores/${activeStore.value.id}/queue` : null)
const catalogTo = computed(() => activeStore.value ? `/stores/${activeStore.value.id}/catalog` : null)
const printersTo = computed(() => activeStore.value ? `/stores/${activeStore.value.id}/printers` : null)
const paymentsTo = computed(() => activeStore.value ? `/stores/${activeStore.value.id}/payments` : null)
const subscriptionTo = computed(() => activeStore.value ? `/stores/${activeStore.value.id}/subscription` : null)

const primaryNav = computed<NavItem[]>(() => [
  { label: 'Dashboard', to: '/dashboard', icon: 'home' },
  ...(queueTo.value ? [{ label: 'Queue', to: queueTo.value, icon: 'columns', permission: 'orders.view' }] : []),
  ...(catalogTo.value ? [{ label: 'Catalog', to: catalogTo.value, icon: 'package', permission: 'catalog.view' }] : []),
  { label: 'Stores', to: '/stores', icon: 'building', permission: 'stores.view' },
  { label: 'Messages', to: '/chat', icon: 'message-circle' },
].filter((item) => !item.permission || authStore.can(item.permission)))

const moreNav = computed<NavItem[]>(() => [
  ...(printersTo.value ? [{ label: 'Printers', to: printersTo.value, icon: 'printer', permission: 'printers.view' }] : []),
  ...(paymentsTo.value ? [{ label: 'Payments', to: paymentsTo.value, icon: 'credit-card', permission: 'stores.update' }] : []),
  ...(subscriptionTo.value ? [{ label: 'Subscription', to: subscriptionTo.value, icon: 'zap', permission: 'subscriptions.view' }] : []),
  { label: 'Users', to: '/users', icon: 'users-group', permission: 'users.view' },
  { label: 'Roles', to: '/roles', icon: 'shield-check', permission: 'roles.view' },
  { label: 'Audit Logs', to: '/audit-logs', icon: 'clipboard-list', permission: 'audit-logs.view' },
].filter((item) => !item.permission || authStore.can(item.permission)))

const roleLabel = computed(() => authStore.primaryRole ?? 'User')
const storeLabel = computed(() => activeStore.value?.name ?? (authStore.isAdmin ? 'All stores' : 'No store selected'))
const isFullWidthPage = computed(() => route.name === 'store-queue')

function isActive(to: string): boolean {
  if (to === '/stores') {
    return route.path === '/stores'
  }

  return route.path === to || route.path.startsWith(to + '/')
}

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
      <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6">
        <RouterLink to="/dashboard" class="flex shrink-0 items-center gap-2">
          <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm shadow-primary-600/20">
            <AppIcon name="printer" :size="18" />
          </span>
          <span class="hidden text-lg font-semibold tracking-normal text-slate-950 dark:text-white sm:inline">Printly</span>
        </RouterLink>

        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button
              class="hidden min-w-0 items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-primary-800 dark:hover:bg-primary-950/40 md:flex"
            >
              <AppIcon name="building" :size="16" class="shrink-0" />
              <span class="max-w-44 truncate">{{ storeLabel }}</span>
              <AppIcon name="chevron-down" :size="14" class="shrink-0 text-slate-400" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent class="w-64" align="start" :side-offset="8">
            <DropdownMenuLabel>Workspace</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              v-for="store in myStores"
              :key="store.id"
              @click="router.push(`/stores/${store.id}/queue`)"
            >
              <AppIcon name="building" :size="16" />
              <span class="min-w-0 truncate">{{ store.name }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem v-if="!myStores.length" @click="router.push('/stores')">
              <AppIcon name="building" :size="16" />
              Manage stores
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <div class="flex flex-1 items-center justify-end gap-1">
          <NotificationBell />
          <button
            class="hidden h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white sm:inline-flex"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <AppIcon :name="isDark ? 'sun' : 'moon'" :size="19" />
          </button>

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <button
                class="flex h-10 items-center gap-2 rounded-full border border-transparent px-1.5 text-left transition hover:border-slate-200 hover:bg-slate-50 dark:hover:border-slate-800 dark:hover:bg-slate-900"
              >
                <AppAvatar :name="authStore.user?.email" size="sm" />
                <span class="hidden min-w-0 leading-tight xl:block">
                  <span class="block max-w-40 truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ authStore.user?.email }}</span>
                  <span class="block truncate text-xs capitalize text-slate-500 dark:text-slate-400">{{ roleLabel }}</span>
                </span>
                <AppIcon name="chevron-down" :size="14" class="hidden text-slate-400 xl:block" />
              </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-60" align="end" :side-offset="8">
              <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                  <AppAvatar :name="authStore.user?.email" size="sm" />
                  <div class="grid min-w-0 flex-1 leading-tight">
                    <span class="truncate font-medium">{{ authStore.user?.email }}</span>
                    <span class="truncate text-xs capitalize text-muted-foreground">{{ roleLabel }}</span>
                  </div>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuGroup>
                <DropdownMenuItem @click="router.push('/settings')">
                  <AppIcon name="settings" :size="16" />
                  Settings
                </DropdownMenuItem>
                <DropdownMenuItem @click="toggleDark">
                  <AppIcon :name="isDark ? 'sun' : 'moon'" :size="16" />
                  {{ isDark ? 'Light mode' : 'Dark mode' }}
                </DropdownMenuItem>
              </DropdownMenuGroup>
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="destructive" @click="handleLogout">
                <AppIcon name="log-out" :size="16" />
                Log out
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>
    </header>

    <main
      class="mx-auto min-w-0 px-4 py-5 pb-[calc(5.75rem+env(safe-area-inset-bottom))] sm:px-6 sm:py-6"
      :class="isFullWidthPage ? 'w-full max-w-none' : 'max-w-7xl'"
    >
      <router-view />
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] pt-2 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
      <div class="mx-auto flex max-w-3xl items-center gap-1 overflow-x-auto rounded-3xl bg-slate-100/70 p-1 dark:bg-slate-900/80 sm:justify-center">
        <RouterLink
          v-for="item in primaryNav"
          :key="item.to"
          :to="item.to"
          class="flex h-14 min-w-16 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-xs font-medium transition sm:flex-none sm:min-w-24"
          :class="isActive(item.to)
            ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white'"
        >
          <AppIcon :name="item.icon" :size="18" />
          {{ item.label }}
        </RouterLink>

        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button class="flex h-14 min-w-16 flex-1 flex-col items-center justify-center gap-1 rounded-2xl px-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white sm:flex-none sm:min-w-24">
              <AppIcon name="ellipsis-horizontal" :size="18" />
              More
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent class="w-60" side="top" align="end" :side-offset="10">
            <DropdownMenuItem
              v-for="item in moreNav"
              :key="item.to"
              @click="router.push(item.to)"
            >
              <AppIcon :name="item.icon" :size="16" />
              {{ item.label }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </nav>
  </div>
</template>
