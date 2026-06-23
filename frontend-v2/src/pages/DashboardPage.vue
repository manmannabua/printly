<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import * as dashboardService from '@/services/dashboardService'
import { getErrorMessage } from '@/services/api'
import type { DashboardData, DashboardKpi } from '@/types/printly'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import DashboardKpiCard from '@/components/dashboard/DashboardKpiCard.vue'
import DashboardAlertPanel from '@/components/dashboard/DashboardAlertPanel.vue'
import AreaChart from '@/components/ui/chart-area/AreaChart.vue'
import BarChart from '@/components/ui/chart-bar/BarChart.vue'
import DonutChart from '@/components/ui/chart-donut/DonutChart.vue'

const data = ref<DashboardData | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const STATUS_COLORS = ['#0891b2', '#f59e0b', '#6366f1', '#8b5cf6', '#10b981', '#22c55e', '#94a3b8', '#ef4444', '#f43f5e', '#a16207']

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function kpiValue(k: DashboardKpi): string {
  return k.format === 'money' ? formatMoney(k.value) : k.value.toLocaleString()
}

function shortDate(d: string): string {
  const parts = d.split('-')
  return parts.length === 3 ? `${Number(parts[1])}/${Number(parts[2])}` : d
}

const subtitle = computed(() =>
  data.value?.role === 'admin'
    ? 'Platform-wide overview'
    : 'Your store at a glance',
)

const hasStatusData = computed(() => (data.value?.status_breakdown.length ?? 0) > 0)
const hasProducts = computed(() => (data.value?.top_products.length ?? 0) > 0)

onMounted(async () => {
  try {
    data.value = await dashboardService.getDashboard()
  } catch (e: unknown) {
    error.value = getErrorMessage(e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <AppPageHeader title="Dashboard" :subtitle="subtitle" />

    <div v-if="error" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
      {{ error }}
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="loading" class="space-y-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-28 animate-pulse rounded-xl bg-gray-100 dark:bg-zinc-800/60" />
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div v-for="i in 2" :key="i" class="h-72 animate-pulse rounded-xl bg-gray-100 dark:bg-zinc-800/60" />
      </div>
    </div>

    <template v-else-if="data">
      <!-- KPI row -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <DashboardKpiCard
          v-for="k in data.kpis"
          :key="k.key"
          :label="k.label"
          :value="kpiValue(k)"
          :icon="k.icon"
          :variant="k.variant ?? 'default'"
        />
      </div>

      <!-- Trends -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <Card class="gap-2">
          <CardHeader><CardTitle class="text-base">Orders · last 14 days</CardTitle></CardHeader>
          <CardContent>
            <AreaChart
              class="h-64"
              :data="data.orders_series"
              index="date"
              :categories="['orders']"
              :colors="['#0891b2']"
              :show-legend="false"
              :x-formatter="shortDate"
            />
          </CardContent>
        </Card>

        <Card class="gap-2">
          <CardHeader><CardTitle class="text-base">Revenue · last 14 days</CardTitle></CardHeader>
          <CardContent>
            <AreaChart
              class="h-64"
              :data="data.revenue_series"
              index="date"
              :categories="['revenue']"
              :colors="['#10b981']"
              :show-legend="false"
              :x-formatter="shortDate"
              :y-formatter="(v: number) => `₱${v.toLocaleString()}`"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Breakdown + attention -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Card class="gap-2">
          <CardHeader><CardTitle class="text-base">Orders by status</CardTitle></CardHeader>
          <CardContent>
            <DonutChart
              v-if="hasStatusData"
              class="h-64"
              :data="data.status_breakdown"
              index="label"
              category="count"
              :colors="STATUS_COLORS"
            />
            <p v-else class="py-16 text-center text-sm text-muted-foreground">No orders yet.</p>
          </CardContent>
        </Card>

        <Card class="gap-2">
          <CardHeader><CardTitle class="text-base">Top products</CardTitle></CardHeader>
          <CardContent>
            <BarChart
              v-if="hasProducts"
              class="h-64"
              :data="data.top_products"
              index="name"
              :categories="['qty']"
              :colors="['#0891b2']"
              orientation="horizontal"
              :show-legend="false"
            />
            <p v-else class="py-16 text-center text-sm text-muted-foreground">No products sold yet.</p>
          </CardContent>
        </Card>

        <DashboardAlertPanel :alerts="data.alerts" />
      </div>
    </template>
  </div>
</template>
