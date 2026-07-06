<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import {
  getSubscription, changePlan,
  type Subscription, type Plan, type SubscriptionStatus,
} from '@/services/subscriptionService'
import type { StorePlan } from '@/types/printly'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog.vue'

const route = useRoute()
const toast = useToast()
const storeId = String(route.params.id)

const loading = ref(true)
const sub = ref<Subscription | null>(null)
const pendingPlan = ref<Plan | null>(null)
const changing = ref(false)

// Friendly labels for the feature keys the API returns.
const FEATURE_LABELS: Record<string, string> = {
  storefront: 'Online storefront & live queue',
  manual_print: 'Manual printing from the dashboard',
  cash_on_pickup: 'Cash on pickup',
  online_payments: 'Online payments — GCash, Maya, card',
  reports: 'Sales & peak-hour analytics',
  chat: 'In-app customer & staff messaging',
  auto_print: 'Auto-print — jobs route straight to your printers',
}

type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'primary'
const STATUS_META: Record<SubscriptionStatus, { label: string; variant: BadgeVariant }> = {
  trialing: { label: 'Trial', variant: 'info' },
  active: { label: 'Active', variant: 'success' },
  past_due: { label: 'Past due', variant: 'warning' },
  canceled: { label: 'Canceled', variant: 'neutral' },
}

function peso(cents: number): string {
  if (cents === 0) return 'Free'
  return `₱${(cents / 100).toLocaleString(undefined, { maximumFractionDigits: 0 })}/mo`
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

const statusMeta = computed(() => (sub.value ? STATUS_META[sub.value.status] : null))

// All features across all plans, in a stable order — drives the comparison rows.
const allFeatures = computed<string[]>(() => {
  const seen = new Set<string>()
  const order: string[] = []
  for (const plan of sub.value?.plans ?? []) {
    for (const f of plan.features) {
      if (!seen.has(f)) { seen.add(f); order.push(f) }
    }
  }
  return order
})

function rank(plan: StorePlan): number {
  return (sub.value?.plans ?? []).findIndex((p) => p.code === plan)
}

function ctaLabel(plan: Plan): string {
  if (!sub.value) return ''
  if (plan.code === sub.value.plan) return 'Current plan'
  return rank(plan.code) > rank(sub.value.plan) ? `Upgrade to ${plan.name}` : `Switch to ${plan.name}`
}

async function load() {
  sub.value = await getSubscription(storeId)
}

onMounted(async () => {
  try { await load() } catch { toast.error('Could not load subscription.') } finally { loading.value = false }
})

async function confirmChange() {
  if (!pendingPlan.value) return
  const target = pendingPlan.value
  pendingPlan.value = null
  changing.value = true
  try {
    sub.value = await changePlan(storeId, target.code)
    toast.success(`You're now on ${target.name}.`)
  } catch {
    toast.error('Could not change plan. Please try again.')
  } finally {
    changing.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-5xl">
    <AppPageHeader title="Subscription" subtitle="Your Printly plan — this is what unlocks online payments, analytics, and auto-print." />

    <div v-if="loading" class="py-16 text-center text-sm text-gray-500">Loading…</div>

    <template v-else-if="sub">
      <!-- Current plan summary -->
      <AppCard class="mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ sub.plan_name }}</span>
              <AppBadge v-if="statusMeta" :variant="statusMeta.variant">{{ statusMeta.label }}</AppBadge>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ peso(sub.price_cents) }}</p>
          </div>
          <div class="text-right text-sm text-gray-500 dark:text-gray-400">
            <p v-if="sub.status === 'trialing' && sub.trial_ends_at">Trial ends {{ formatDate(sub.trial_ends_at) }}</p>
            <p v-else-if="sub.current_period_end">Renews {{ formatDate(sub.current_period_end) }}</p>
          </div>
        </div>

        <!-- What this plan includes -->
        <div class="mt-4 grid gap-2 border-t border-gray-100 pt-4 sm:grid-cols-2 dark:border-gray-800">
          <div v-for="f in sub.features" :key="f" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <AppIcon name="check" class="h-4 w-4 shrink-0 text-success-500" />
            <span>{{ FEATURE_LABELS[f] ?? f }}</span>
          </div>
        </div>

        <p v-if="!sub.can_manage" class="mt-4 rounded-md bg-gray-50 p-3 text-xs text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
          Only Printly can change your plan. Message us from
          <RouterLink to="/chat" class="font-medium text-primary-600 hover:underline">Messages</RouterLink>
          to upgrade or downgrade.
        </p>
      </AppCard>

      <!-- Plan comparison -->
      <div class="grid gap-4 md:grid-cols-3">
        <AppCard
          v-for="plan in sub.plans" :key="plan.code"
          :class="plan.code === sub.plan ? 'ring-2 ring-primary-500' : ''"
        >
          <div class="mb-3">
            <div class="flex items-center justify-between">
              <span class="text-base font-bold text-gray-900 dark:text-gray-100">{{ plan.name }}</span>
              <AppBadge v-if="plan.code === sub.plan" variant="primary">Current</AppBadge>
            </div>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ peso(plan.price_cents) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ plan.tagline }}</p>
          </div>

          <ul class="mb-4 space-y-2">
            <li
              v-for="f in allFeatures" :key="f"
              class="flex items-center gap-2 text-sm"
              :class="plan.features.includes(f) ? 'text-gray-700 dark:text-gray-200' : 'text-gray-300 line-through dark:text-gray-600'"
            >
              <AppIcon
                :name="plan.features.includes(f) ? 'check' : 'x'"
                class="h-4 w-4 shrink-0"
                :class="plan.features.includes(f) ? 'text-success-500' : 'text-gray-300 dark:text-gray-600'"
              />
              <span>{{ FEATURE_LABELS[f] ?? f }}</span>
            </li>
          </ul>

          <AppButton
            v-if="sub.can_manage"
            class="w-full"
            :variant="plan.code === sub.plan ? 'secondary' : 'primary'"
            :disabled="plan.code === sub.plan || changing"
            @click="pendingPlan = plan"
          >
            <span>{{ ctaLabel(plan) }}</span>
          </AppButton>
          <div v-else-if="plan.code === sub.plan" class="rounded-md bg-primary-50 py-2 text-center text-sm font-medium text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
            Current plan
          </div>
        </AppCard>
      </div>
    </template>

    <AppConfirmDialog
      :model-value="pendingPlan !== null"
      :title="pendingPlan ? `Switch to ${pendingPlan.name}?` : ''"
      :message="pendingPlan ? `Your store will move to the ${pendingPlan.name} plan (${peso(pendingPlan.price_cents)}) and its features take effect right away.` : ''"
      confirm-label="Confirm"
      @update:model-value="(v: boolean) => { if (!v) pendingPlan = null }"
      @confirm="confirmChange"
    />
  </div>
</template>
