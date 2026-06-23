<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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
interface NavGroup {
  label: string
  items: NavItem[]
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { isDark, toggle: toggleDark } = useDarkMode()

const sidebarOpen = ref(false)

// The user's stores (membership-scoped for non-admins) power the direct Queue
// link. Only fetched for store members — admins reach queues via the store list.
const myStores = ref<Store[]>([])
onMounted(async () => {
  if (!authStore.isAdmin && authStore.can('orders.view')) {
    try { myStores.value = await listStores() } catch { /* non-critical */ }
  }
})
const queueTo = computed(() => {
  const first = myStores.value[0]
  return first ? `/stores/${first.id}/queue` : null
})

const groups = computed<NavGroup[]>(() => [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', to: '/dashboard', icon: 'home' },
      ...(queueTo.value ? [{ label: 'Queue', to: queueTo.value, icon: 'columns', permission: 'orders.view' }] : []),
      { label: 'Messages', to: '/chat', icon: 'message-circle' },
    ],
  },
  {
    label: 'Catalog',
    items: [
      { label: 'Stores', to: '/stores', icon: 'building', permission: 'stores.view' },
    ],
  },
  {
    label: 'Administration',
    items: [
      { label: 'Users', to: '/users', icon: 'users-group', permission: 'users.view' },
      { label: 'Roles', to: '/roles', icon: 'shield-check', permission: 'roles.view' },
      { label: 'Audit Logs', to: '/audit-logs', icon: 'clipboard-list', permission: 'audit-logs.view' },
    ],
  },
])

const visibleGroups = computed(() =>
  groups.value
    .map((g) => ({
      ...g,
      items: g.items.filter((i) => !i.permission || authStore.can(i.permission)),
    }))
    .filter((g) => g.items.length > 0),
)

function isActive(to: string): boolean {
  return route.path === to || route.path.startsWith(to + '/')
}

const roleLabel = computed(() => authStore.primaryRole ?? 'User')

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-50 dark:bg-black">
    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform dark:border-gray-800 dark:bg-gray-900 lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-5 dark:border-gray-800">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600 text-white">
          <AppIcon name="printer" :size="18" />
        </span>
        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">Printly</span>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4">
        <div v-for="group in visibleGroups" :key="group.label" class="mb-5">
          <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ group.label }}
          </p>
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
            :class="isActive(item.to)
              ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300'
              : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'"
            @click="sidebarOpen = false"
          >
            <AppIcon :name="item.icon" :size="18" />
            {{ item.label }}
          </RouterLink>
        </div>
      </nav>

      <!-- Footer: user avatar → dropdown menu -->
      <div class="border-t border-gray-200 p-3 dark:border-gray-800">
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button
              class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors hover:bg-gray-100 data-[state=open]:bg-gray-100 dark:hover:bg-gray-800 dark:data-[state=open]:bg-gray-800"
            >
              <AppAvatar :name="authStore.user?.email" size="sm" />
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ authStore.user?.email }}</span>
                <span class="block truncate text-xs capitalize text-gray-400">{{ roleLabel }}</span>
              </span>
              <AppIcon name="dots-vertical" :size="16" class="shrink-0 text-gray-400" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent class="w-56" side="top" align="start" :side-offset="8">
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
    </aside>

    <!-- Main column -->
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
      <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900">
        <button
          class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 lg:hidden"
          @click="sidebarOpen = true"
        >
          <AppIcon name="menu" :size="22" />
        </button>
        <div class="flex-1" />
        <div class="flex items-center gap-3">
          <NotificationBell />
          <button
            class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <AppIcon :name="isDark ? 'sun' : 'moon'" :size="20" />
          </button>
        </div>
      </header>

      <main class="min-w-0 flex-1 p-4 sm:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>
