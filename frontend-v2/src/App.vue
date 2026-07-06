<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { setToastRef } from '@/composables/useToast'
import { updateServiceWorker } from '@/pwa'
import LoginLayout from '@/layouts/LoginLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import StorefrontLayout from '@/layouts/StorefrontLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import AppToast from '@/components/common/AppToast.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const route = useRoute()
const authStore = useAuthStore()
const updateAvailable = ref(false)
const offlineReady = ref(false)

const currentLayout = computed(() => {
  switch (route.meta.layout) {
    case 'login':      return LoginLayout
    case 'guest':      return GuestLayout
    case 'storefront': return StorefrontLayout
    case 'dashboard':  return DashboardLayout
    default:           return 'div'
  }
})

const waitingForAuth = computed(() => {
  return route.meta.requiresAuth !== false && !authStore.initialized
})

function onToastRef(el: unknown): void {
  setToastRef(el as InstanceType<typeof AppToast>)
}

onMounted(() => {
  window.addEventListener('printly:pwa-update', () => {
    updateAvailable.value = true
    offlineReady.value = false
  })
  window.addEventListener('printly:pwa-offline-ready', () => {
    if (!updateAvailable.value) {
      offlineReady.value = true
      window.setTimeout(() => {
        offlineReady.value = false
      }, 5000)
    }
  })
})

function reloadForUpdate(): void {
  void updateServiceWorker(true)
}
</script>

<template>
  <!-- Loading state during auth initialization -->
  <div v-if="waitingForAuth" class="flex h-screen items-center justify-center bg-gray-50 dark:bg-black">
    <div class="text-center">
      <AppSpinner size="lg" />
      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Loading...</p>
    </div>
  </div>

  <!-- App content -->
  <template v-else>
    <component :is="currentLayout">
      <template v-if="!route.meta.layout">
        <router-view />
      </template>
    </component>
  </template>

  <!-- Toast notifications -->
  <AppToast :ref="onToastRef" />

  <div
    v-if="updateAvailable || offlineReady"
    class="fixed inset-x-3 bottom-3 z-[80] mx-auto flex max-w-md items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
  >
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
      <AppIcon :name="updateAvailable ? 'refresh-cw' : 'smartphone'" :size="20" />
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-semibold text-slate-950 dark:text-white">
        {{ updateAvailable ? 'Update available' : 'Ready offline' }}
      </p>
      <p class="truncate text-xs text-slate-500">
        {{ updateAvailable ? 'Refresh to use the latest Printly version.' : 'Printly can now open faster on this device.' }}
      </p>
    </div>
    <button
      v-if="updateAvailable"
      class="rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white"
      @click="reloadForUpdate"
    >
      Update
    </button>
    <button
      v-else
      class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300"
      @click="offlineReady = false"
    >
      OK
    </button>
  </div>
</template>
