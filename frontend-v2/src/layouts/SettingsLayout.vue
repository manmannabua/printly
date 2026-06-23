<script setup lang="ts">
import { useRoute } from 'vue-router'
import AppIcon from '@/components/common/AppIcon.vue'

const route = useRoute()

interface SettingsSection {
  label: string
  icon: string
  to: string
}

const sections: SettingsSection[] = [
  { label: 'General', icon: 'settings', to: '/settings/general' },
  { label: 'Security', icon: 'shield-check', to: '/settings/security' },
]

function isActive(to: string): boolean {
  return route.path === to
}
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
      <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Manage your account preferences and security</p>
    </div>

    <div class="flex flex-col gap-6 md:flex-row md:gap-8">
      <!-- Secondary sidebar (sticky on desktop, horizontal on mobile) -->
      <nav class="shrink-0 md:w-56 md:self-start" style="position: sticky; top: 1.5rem;">
        <ul class="flex gap-1 overflow-x-auto md:flex-col md:space-y-1 md:overflow-visible">
          <li v-for="section in sections" :key="section.to">
            <router-link
              :to="section.to"
              class="flex items-center gap-3 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="isActive(section.to)
                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'"
            >
              <AppIcon :name="section.icon" :size="18" />
              <span>{{ section.label }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- Content -->
      <div class="min-w-0 flex-1 pb-8">
        <router-view />
      </div>
    </div>
  </div>
</template>
