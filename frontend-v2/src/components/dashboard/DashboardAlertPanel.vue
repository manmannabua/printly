<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import AppIcon from '@/components/common/AppIcon.vue'
import type { DashboardAlert } from '@/types/printly'

defineProps<{ alerts: DashboardAlert[] }>()

const STYLES: Record<DashboardAlert['severity'], { dot: string, icon: string }> = {
  danger: { dot: 'bg-rose-500', icon: 'alert-circle' },
  warning: { dot: 'bg-amber-500', icon: 'alert-triangle' },
  info: { dot: 'bg-blue-500', icon: 'info-circle' },
}
</script>

<template>
  <Card class="gap-2">
    <CardHeader>
      <CardTitle class="text-base">Needs attention</CardTitle>
    </CardHeader>
    <CardContent>
      <div v-if="alerts.length === 0" class="flex flex-col items-center gap-2 py-6 text-center">
        <AppIcon name="circle-check" :size="28" class="text-emerald-500" />
        <p class="text-sm text-muted-foreground">All clear — nothing needs attention.</p>
      </div>
      <ul v-else class="space-y-2">
        <li
          v-for="(a, i) in alerts"
          :key="i"
          class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2.5 dark:border-zinc-800"
        >
          <span class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200">
            <span class="h-2 w-2 rounded-full" :class="STYLES[a.severity].dot" />
            {{ a.label }}
          </span>
          <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ a.count }}</span>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
