<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMediaQuery } from '@vueuse/core'
import { useAuthStore } from '@/stores/auth'
import AppIcon from '@/components/common/AppIcon.vue'

export interface Breadcrumb {
  label: string
  to?: string
}

defineProps<{
  title: string
  subtitle?: string
  breadcrumbs?: Breadcrumb[]
}>()

const router = useRouter()
const route = useRoute()
const isMobile = useMediaQuery('(max-width: 768px)')
const authStore = useAuthStore()

// Show a back button for mobile employees on every page except the dashboard.
// Route is tracked so the computed re-evaluates on navigation.
const showBack = computed(() =>
  isMobile.value &&
  authStore.roleLevel === 3 &&
  route.path !== '/dashboard',
)
</script>

<template>
  <div class="mb-6">
    <!-- Breadcrumbs (desktop only) -->
    <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="mb-2 hidden sm:block" aria-label="Breadcrumb">
      <ol class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center gap-1">
          <span v-if="index > 0" class="text-gray-300 dark:text-gray-600">/</span>
          <router-link v-if="crumb.to" :to="crumb.to" class="hover:text-gray-700 dark:hover:text-gray-300">
            {{ crumb.label }}
          </router-link>
          <span v-else class="text-gray-900 dark:text-gray-100">{{ crumb.label }}</span>
        </li>
      </ol>
    </nav>

    <!-- Title row -->
    <div class="flex items-center justify-between gap-3 sm:items-start sm:gap-4">
      <div class="flex min-w-0 items-center gap-2">
        <!-- Mobile back button (employee only, not on dashboard) -->
        <button
          v-if="showBack"
          type="button"
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors active:bg-muted sm:hidden"
          aria-label="Go back"
          @click="router.back()"
        >
          <AppIcon name="arrow-left" :size="20" />
        </button>

        <div class="min-w-0">
          <h1 class="flex flex-wrap items-center gap-2 text-xl font-bold text-gray-900 sm:text-2xl dark:text-gray-100">
            {{ title }}
            <slot name="after-title" />
          </h1>
          <p v-if="subtitle" class="mt-1 hidden text-sm text-gray-500 sm:block dark:text-gray-400">
            {{ subtitle }}
          </p>
        </div>
      </div>

      <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2 [&_button>span]:hidden [&_button>span]:sm:inline">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
