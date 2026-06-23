<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useIntervalFn } from '@vueuse/core'
import api, { getErrorMessage } from '@/services/api'
import type { ApiResponse } from '@/types/api'
import type { Order, OrderStatus, Store } from '@/types/printly'
import { fileDownloadUrl, getOrder, transitionOrder } from '@/services/orderService'
import { subscribeToStoreOrders } from '@/services/echo'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const storeId = String(route.params.id)

const store = ref<Store | null>(null)
const orders = ref<Order[]>([])
const loading = ref(true)
const expanded = ref<Record<string, Order>>({}) // orderId -> detailed order
const busy = ref<Record<string, boolean>>({})

const canProcess = computed(() => auth.can('orders.process'))

// ── Tabs ──────────────────────────────────────────────────────────────────────
const TABS: { key: string, label: string, statuses: OrderStatus[] }[] = [
  { key: 'new', label: 'New', statuses: ['pending_payment'] },
  { key: 'active', label: 'In queue', statuses: ['paid', 'accepted', 'in_progress', 'ready'] },
  { key: 'done', label: 'Completed', statuses: ['completed'] },
  { key: 'issues', label: 'Issues', statuses: ['cancelled', 'rejected', 'failed', 'refunded'] },
]
const activeTab = ref('active')

const grouped = computed(() => {
  const map: Record<string, Order[]> = {}
  for (const tab of TABS) map[tab.key] = []
  for (const o of orders.value) {
    const tab = TABS.find(t => t.statuses.includes(o.status))
    if (tab) map[tab.key].push(o)
  }
  return map
})
const visibleOrders = computed(() => grouped.value[activeTab.value] ?? [])

// ── Transition actions per status (mirror backend state machine) ───────────────
interface Action { label: string, to: OrderStatus, icon: string, danger?: boolean }
const ACTIONS: Partial<Record<OrderStatus, Action[]>> = {
  pending_payment: [
    { label: 'Mark paid', to: 'paid', icon: 'credit-card' },
    { label: 'Cancel', to: 'cancelled', icon: 'x-circle', danger: true },
  ],
  paid: [
    { label: 'Accept', to: 'accepted', icon: 'check' },
    { label: 'Reject', to: 'rejected', icon: 'x-circle', danger: true },
  ],
  accepted: [
    { label: 'Start printing', to: 'in_progress', icon: 'printer' },
    { label: 'Reject', to: 'rejected', icon: 'x-circle', danger: true },
  ],
  in_progress: [
    { label: 'Mark ready', to: 'ready', icon: 'package' },
    { label: 'Failed', to: 'failed', icon: 'alert-triangle', danger: true },
  ],
  ready: [
    { label: 'Complete', to: 'completed', icon: 'badge-check' },
    { label: 'Failed', to: 'failed', icon: 'alert-triangle', danger: true },
  ],
  rejected: [{ label: 'Mark refunded', to: 'refunded', icon: 'wallet' }],
  failed: [{ label: 'Mark refunded', to: 'refunded', icon: 'wallet' }],
}

const STATUS_VARIANT: Record<string, 'neutral' | 'info' | 'success' | 'danger'> = {
  pending_payment: 'neutral', paid: 'info', accepted: 'info', in_progress: 'info',
  ready: 'success', completed: 'success',
  cancelled: 'danger', rejected: 'danger', failed: 'danger', refunded: 'neutral',
}

function statusLabel(s: string): string {
  return s.replace(/_/g, ' ')
}

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function relativeTime(iso: string | null): string {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.round(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.round(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.round(hrs / 24)}d ago`
}

// ── Data ──────────────────────────────────────────────────────────────────────
async function load(): Promise<void> {
  try {
    const res = await api.get<ApiResponse<Order[]>>(`/api/v1/stores/${storeId}/orders`, {
      params: { all: true, sort_by: 'placed_at', sort_dir: 'desc' },
    })
    orders.value = res.data.data
  } catch (e) {
    toast.error(getErrorMessage(e))
  } finally {
    loading.value = false
  }
}

async function toggleExpand(order: Order): Promise<void> {
  if (expanded.value[order.id]) {
    delete expanded.value[order.id]
    return
  }
  try {
    expanded.value[order.id] = await getOrder(storeId, order.id)
  } catch (e) {
    toast.error(getErrorMessage(e))
  }
}

async function act(order: Order, action: Action): Promise<void> {
  busy.value[order.id] = true
  try {
    const updated = await transitionOrder(storeId, order.id, action.to)
    // Update in place so the card moves to the right tab immediately.
    const idx = orders.value.findIndex(o => o.id === order.id)
    if (idx !== -1) orders.value[idx] = { ...orders.value[idx], ...updated }
    if (expanded.value[order.id]) expanded.value[order.id] = await getOrder(storeId, order.id)
    toast.success(`Order ${order.code} → ${statusLabel(action.to)}`)
  } catch (e) {
    toast.error(getErrorMessage(e))
  } finally {
    busy.value[order.id] = false
  }
}

// ── Real-time (with polling fallback) ──────────────────────────────────────────
let unsubscribe: (() => void) | null = null
const { pause: stopPolling } = useIntervalFn(load, 12000)

onMounted(async () => {
  try {
    const res = await api.get<ApiResponse<Store>>(`/api/v1/stores/${storeId}`)
    store.value = res.data.data
  } catch { /* header is non-critical */ }

  await load()

  unsubscribe = subscribeToStoreOrders(storeId, {
    onPlaced: () => load(),
    onStatusChanged: () => load(),
  })
})

onUnmounted(() => {
  stopPolling()
  unsubscribe?.()
})
</script>

<template>
  <div>
    <AppPageHeader
      :title="store ? `${store.name} — Queue` : 'Order Queue'"
      subtitle="Live order board"
      :breadcrumbs="[{ label: 'Stores', to: '/stores' }, { label: 'Queue' }]"
    >
      <template #actions>
        <AppButton variant="secondary" icon="refresh" @click="load">
          <span>Refresh</span>
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Tabs -->
    <div class="mb-4 flex gap-1 overflow-x-auto rounded-lg border border-gray-200 p-1 dark:border-zinc-800">
      <button
        v-for="tab in TABS" :key="tab.key"
        class="flex-1 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition"
        :class="activeTab === tab.key ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-zinc-800'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
        <span class="ml-1 opacity-70">{{ grouped[tab.key]?.length ?? 0 }}</span>
      </button>
    </div>

    <div v-if="loading" class="flex justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="!visibleOrders.length" class="rounded-xl border border-dashed border-gray-300 py-16 text-center text-gray-400 dark:border-zinc-700">
      <AppIcon name="queue-list" :size="36" class="mx-auto" />
      <p class="mt-2 text-sm">No orders here.</p>
    </div>

    <div v-else class="space-y-3">
      <AppCard v-for="order in visibleOrders" :key="order.id" no-padding>
        <div class="p-4">
          <!-- Row header -->
          <div class="flex items-start justify-between gap-3">
            <button class="min-w-0 text-left" @click="toggleExpand(order)">
              <div class="flex items-center gap-2">
                <span class="font-mono font-semibold text-gray-900 dark:text-white">{{ order.code }}</span>
                <AppBadge :variant="STATUS_VARIANT[order.status] ?? 'neutral'">{{ statusLabel(order.status) }}</AppBadge>
              </div>
              <p class="mt-1 truncate text-sm text-gray-500">
                {{ (order.items ?? []).map(i => `${i.product_name} ×${i.quantity}`).join(', ') || '—' }}
              </p>
              <p class="text-xs text-gray-400">{{ relativeTime(order.placed_at) }}</p>
            </button>
            <div class="shrink-0 text-right">
              <p class="font-semibold text-gray-900 dark:text-white">{{ formatMoney(order.total_cents) }}</p>
              <p class="text-xs capitalize text-gray-400">{{ statusLabel(order.payment_status) }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="canProcess && ACTIONS[order.status]?.length" class="mt-3 flex flex-wrap gap-2">
            <AppButton
              v-for="a in ACTIONS[order.status]" :key="a.to"
              :variant="a.danger ? 'secondary' : 'primary'"
              :icon="a.icon"
              size="sm"
              :loading="busy[order.id]"
              :class="a.danger ? 'text-danger-600' : ''"
              @click="act(order, a)"
            >
              <span>{{ a.label }}</span>
            </AppButton>
          </div>

          <!-- Expanded detail -->
          <div v-if="expanded[order.id]" class="mt-4 space-y-4 border-t border-gray-100 pt-4 dark:border-zinc-800">
            <div v-if="order.notes" class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600 dark:bg-zinc-800 dark:text-gray-300">
              <span class="font-medium">Note:</span> {{ order.notes }}
            </div>

            <div v-for="item in expanded[order.id].items ?? []" :key="item.id">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ item.product_name }} <span class="text-gray-400">×{{ item.quantity }}</span>
                <span class="float-right">{{ formatMoney(item.line_total_cents) }}</span>
              </p>
              <div v-if="item.files?.length" class="mt-1 space-y-1">
                <a
                  v-for="file in item.files" :key="file.id"
                  :href="fileDownloadUrl(storeId, file.id)"
                  target="_blank" rel="noopener"
                  class="flex items-center gap-2 text-sm text-primary-600 hover:underline dark:text-primary-400"
                >
                  <AppIcon name="download" :size="16" />
                  <span class="truncate">{{ file.original_name }}</span>
                  <span class="text-xs text-gray-400">
                    {{ file.page_count ? `${file.page_count}p` : '' }}{{ file.paper_size ? ` · ${file.paper_size}` : '' }}
                  </span>
                </a>
              </div>
            </div>

            <!-- Timeline -->
            <ol v-if="expanded[order.id].events?.length" class="space-y-1 text-xs text-gray-400">
              <li v-for="ev in expanded[order.id].events" :key="ev.id">
                {{ statusLabel(ev.to_status) }} · {{ ev.actor_type }} · {{ relativeTime(ev.created_at ?? null) }}
              </li>
            </ol>
          </div>
        </div>
      </AppCard>
    </div>
  </div>
</template>
