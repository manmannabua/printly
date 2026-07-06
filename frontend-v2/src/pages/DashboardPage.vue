<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import * as dashboardService from '@/services/dashboardService'
import { getErrorMessage } from '@/services/api'
import { getPaymentSettings, type PaymentSettings } from '@/services/paymentSettingsService'
import { listStores } from '@/services/storeService'
import type { DashboardData, DashboardKpi, Store } from '@/types/printly'
import AppIcon from '@/components/common/AppIcon.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import DashboardKpiCard from '@/components/dashboard/DashboardKpiCard.vue'
import DashboardAlertPanel from '@/components/dashboard/DashboardAlertPanel.vue'
import StorePublishChecklist from '@/components/stores/StorePublishChecklist.vue'
import AreaChart from '@/components/ui/chart-area/AreaChart.vue'
import BarChart from '@/components/ui/chart-bar/BarChart.vue'
import DonutChart from '@/components/ui/chart-donut/DonutChart.vue'

interface ActionLink {
  label: string
  description: string
  to: string
  icon: string
  tone: string
}

const data = ref<DashboardData | null>(null)
const stores = ref<Store[]>([])
const firstStorePaymentSettings = ref<PaymentSettings | null>(null)
const loading = ref(true)
const paymentSettingsLoading = ref(false)
const error = ref<string | null>(null)

const STATUS_COLORS = ['#0891b2', '#f59e0b', '#6366f1', '#8b5cf6', '#10b981', '#22c55e', '#94a3b8', '#ef4444', '#f43f5e', '#a16207']

function formatMoney(cents: number): string {
  return `PHP ${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function formatRevenue(value: number): string {
  return `PHP ${value.toLocaleString()}`
}

function kpiValue(kpi: DashboardKpi): string {
  return kpi.format === 'money' ? formatMoney(kpi.value) : kpi.value.toLocaleString()
}

function shortDate(date: string): string {
  const parts = date.split('-')
  return parts.length === 3 ? `${Number(parts[1])}/${Number(parts[2])}` : date
}

function statusCount(status: string): number {
  return data.value?.status_breakdown.find((item) => item.status === status)?.count ?? 0
}

const firstStore = computed(() => stores.value[0] ?? null)
const hasStatusData = computed(() => (data.value?.status_breakdown.length ?? 0) > 0)
const hasProducts = computed(() => (data.value?.top_products.length ?? 0) > 0)
const totalOpenOrders = computed(() =>
  ['pending_payment', 'accepted', 'in_progress', 'ready'].reduce((sum, status) => sum + statusCount(status), 0),
)
const totalAlerts = computed(() => data.value?.alerts.reduce((sum, alert) => sum + alert.count, 0) ?? 0)
const dashboardScope = computed(() => data.value?.role === 'admin' ? 'Platform operations' : firstStore.value?.name ?? 'Store operations')

const workflow = computed(() => [
  { label: 'Payment', status: 'pending_payment', count: statusCount('pending_payment'), icon: 'credit-card', tone: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900' },
  { label: 'Accepted', status: 'accepted', count: statusCount('accepted'), icon: 'badge-check', tone: 'bg-cyan-50 text-cyan-700 ring-cyan-200 dark:bg-cyan-950/30 dark:text-cyan-300 dark:ring-cyan-900' },
  { label: 'Production', status: 'in_progress', count: statusCount('in_progress'), icon: 'printer', tone: 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-300 dark:ring-indigo-900' },
  { label: 'Ready', status: 'ready', count: statusCount('ready'), icon: 'circle-check', tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900' },
])

const quickActions = computed<ActionLink[]>(() => {
  if (firstStore.value) {
    const id = firstStore.value.id
    return [
      { label: 'Open queue', description: 'Review active jobs', to: `/stores/${id}/queue`, icon: 'columns', tone: 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' },
      { label: 'Update catalog', description: 'Products and pricing', to: `/stores/${id}/catalog`, icon: 'package', tone: 'bg-white text-slate-900 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-white dark:ring-slate-800' },
      { label: 'Payment setup', description: 'Checkout settings', to: `/stores/${id}/payments`, icon: 'credit-card', tone: 'bg-white text-slate-900 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-white dark:ring-slate-800' },
      { label: 'Messages', description: 'Customer chats', to: '/chat', icon: 'message-circle', tone: 'bg-white text-slate-900 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-white dark:ring-slate-800' },
    ]
  }

  return [
    { label: 'Manage stores', description: 'Open store list', to: '/stores', icon: 'building', tone: 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' },
    { label: 'Messages', description: 'Customer chats', to: '/chat', icon: 'message-circle', tone: 'bg-white text-slate-900 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-white dark:ring-slate-800' },
  ]
})

function updateVisibleStore(updatedStore: Store): void {
  stores.value = stores.value.map((store) => store.id === updatedStore.id ? updatedStore : store)
}

onMounted(async () => {
  try {
    const [dashboard, visibleStores] = await Promise.all([
      dashboardService.getDashboard(),
      listStores().catch(() => []),
    ])
    data.value = dashboard
    stores.value = visibleStores

    if (visibleStores[0]) {
      paymentSettingsLoading.value = true
      getPaymentSettings(visibleStores[0].id)
        .then((settings) => {
          firstStorePaymentSettings.value = settings
        })
        .catch(() => {
          firstStorePaymentSettings.value = null
        })
        .finally(() => {
          paymentSettingsLoading.value = false
        })
    }
  } catch (e: unknown) {
    error.value = getErrorMessage(e)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-5">
    <div v-if="error" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700 ring-1 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50">
      {{ error }}
    </div>

    <div v-else-if="loading" class="space-y-5">
      <div class="h-56 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-900" />
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-28 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-900" />
      </div>
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="h-72 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-900 lg:col-span-2" />
        <div class="h-72 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-900" />
      </div>
    </div>

    <template v-else-if="data">
      <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:p-6">
          <div class="min-w-0">
            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">{{ dashboardScope }}</p>
            <div class="mt-2 flex flex-wrap items-end gap-x-4 gap-y-2">
              <h1 class="text-2xl font-semibold tracking-normal text-slate-950 dark:text-white sm:text-3xl">Command center</h1>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ totalOpenOrders }} open orders
              </span>
            </div>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
              Monitor order movement, production pressure, customer conversations, and store setup from one focused workspace.
            </p>

            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div
                v-for="step in workflow"
                :key="step.status"
                class="rounded-xl p-3 ring-1"
                :class="step.tone"
              >
                <div class="flex items-center justify-between gap-2">
                  <AppIcon :name="step.icon" :size="18" />
                  <span class="text-xl font-semibold tabular-nums">{{ step.count }}</span>
                </div>
                <p class="mt-2 text-xs font-medium uppercase tracking-normal opacity-80">{{ step.label }}</p>
              </div>
            </div>
          </div>

          <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 dark:bg-slate-950/50 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Needs attention</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums text-slate-950 dark:text-white">{{ totalAlerts }}</p>
              </div>
              <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-amber-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-amber-300 dark:ring-slate-800">
                <AppIcon name="alert-triangle" :size="21" />
              </span>
            </div>
            <div class="mt-4 grid gap-2">
              <RouterLink
                v-for="action in quickActions"
                :key="action.to + action.label"
                :to="action.to"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:-translate-y-0.5 hover:shadow-sm"
                :class="action.tone"
              >
                <AppIcon :name="action.icon" :size="18" />
                <span class="min-w-0 flex-1">
                  <span class="block truncate font-semibold">{{ action.label }}</span>
                  <span class="block truncate text-xs opacity-70">{{ action.description }}</span>
                </span>
                <AppIcon name="chevron-right" :size="16" class="opacity-60" />
              </RouterLink>
            </div>
          </div>
        </div>
      </section>

      <StorePublishChecklist
        v-if="firstStore"
        :store="firstStore"
        :payment-settings="firstStorePaymentSettings"
        :loading-payment="paymentSettingsLoading"
        @updated="updateVisibleStore"
      />

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <DashboardKpiCard
          v-for="kpi in data.kpis"
          :key="kpi.key"
          :label="kpi.label"
          :value="kpiValue(kpi)"
          :icon="kpi.icon"
          :variant="kpi.variant ?? 'default'"
        />
      </div>

      <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Card class="gap-2 lg:col-span-2">
          <CardHeader>
            <CardTitle class="text-base">Order intake</CardTitle>
          </CardHeader>
          <CardContent>
            <AreaChart
              class="h-72"
              :data="data.orders_series"
              index="date"
              :categories="['orders']"
              :colors="['#0891b2']"
              :show-legend="false"
              :x-formatter="shortDate"
            />
          </CardContent>
        </Card>

        <DashboardAlertPanel :alerts="data.alerts" />
      </section>

      <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Card class="gap-2">
          <CardHeader>
            <CardTitle class="text-base">Orders by status</CardTitle>
          </CardHeader>
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
          <CardHeader>
            <CardTitle class="text-base">Top products</CardTitle>
          </CardHeader>
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

        <Card class="gap-2">
          <CardHeader>
            <CardTitle class="text-base">Revenue trend</CardTitle>
          </CardHeader>
          <CardContent>
            <AreaChart
              class="h-64"
              :data="data.revenue_series"
              index="date"
              :categories="['revenue']"
              :colors="['#10b981']"
              :show-legend="false"
              :x-formatter="shortDate"
              :y-formatter="formatRevenue"
            />
          </CardContent>
        </Card>
      </section>
    </template>
  </div>
</template>
