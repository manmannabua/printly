<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useDarkMode } from '@/composables/useDarkMode'
import AppCard from '@/components/ui/AppCard.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'

const auth = useAuthStore()
const { isDark, toggle: toggleDark } = useDarkMode()
</script>

<template>
  <div class="space-y-5">
    <!-- Identity -->
    <AppCard>
      <div class="flex items-center gap-3 p-4">
        <AppAvatar :name="auth.user?.email" size="lg" />
        <div class="min-w-0">
          <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ auth.user?.email }}</p>
          <p class="text-xs capitalize text-gray-400">{{ auth.primaryRole ?? 'User' }}</p>
        </div>
      </div>
    </AppCard>

    <!-- Appearance -->
    <AppCard>
      <div class="p-4">
        <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Appearance</h4>
        <AppToggle
          :model-value="isDark"
          label="Dark mode"
          description="Switch between light and dark appearance."
          @update:model-value="toggleDark"
        />
      </div>
    </AppCard>
  </div>
</template>
