<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { setToastRef } from '@/composables/useToast'
import LoginLayout from '@/layouts/LoginLayout.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
import StorefrontLayout from '@/layouts/StorefrontLayout.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import AppToast from '@/components/common/AppToast.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const route = useRoute()
const authStore = useAuthStore()

const currentLayout = computed(() => {
  switch (route.meta.layout) {
    case 'login':      return LoginLayout
    case 'guest':      return GuestLayout
    case 'storefront': return StorefrontLayout
    case 'dashboard':  return DashboardLayout
    default:           return 'div'
  }
})

function onToastRef(el: unknown): void {
  setToastRef(el as InstanceType<typeof AppToast>)
}
</script>

<template>
  <!-- Loading state during auth initialization -->
  <div v-if="!authStore.initialized" class="flex h-screen items-center justify-center bg-gray-50 dark:bg-black">
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
</template>
