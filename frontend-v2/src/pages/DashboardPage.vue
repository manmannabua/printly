<script setup lang="ts">
import { ref, onMounted } from 'vue'
import * as dashboardService from '@/services/dashboardService'
import { getErrorMessage } from '@/services/api'
import type { DashboardCounts } from '@/types/printly'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const counts = ref<DashboardCounts | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

interface Metric {
  label: string
  value: () => number
  icon: string
  accent: string
}

const metrics: Metric[] = [
  { label: 'Users', value: () => counts.value?.users ?? 0, icon: 'users', accent: 'text-blue-600 bg-blue-50 dark:bg-blue-950/40' },
  { label: 'Active Users', value: () => counts.value?.active_users ?? 0, icon: 'check-circle', accent: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40' },
]

onMounted(async () => {
  try {
    counts.value = await dashboardService.getDashboard()
  } catch (e: unknown) {
    error.value = getErrorMessage(e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <AppPageHeader title="Dashboard" subtitle="Overview of your Printly workspace" />

    <div v-if="error" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <AppCard v-for="m in metrics" :key="m.label">
        <div class="flex items-center gap-4">
          <span class="flex h-12 w-12 items-center justify-center rounded-xl" :class="m.accent">
            <AppIcon :name="m.icon" :size="22" />
          </span>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
              <span v-if="loading" class="text-gray-300">â€”</span>
              <span v-else>{{ m.value().toLocaleString() }}</span>
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ m.label }}</p>
          </div>
        </div>
      </AppCard>
    </div>
  </div>
</template>
