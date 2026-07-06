<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Store } from '@/types/printly'
import type { PaymentSettings } from '@/services/paymentSettingsService'
import { updateStore } from '@/services/storeService'
import { useToast } from '@/composables/useToast'
import AppIcon from '@/components/common/AppIcon.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const props = defineProps<{
  store: Store
  paymentSettings?: PaymentSettings | null
  loadingPayment?: boolean
}>()

const emit = defineEmits<{
  updated: [store: Store]
}>()

const toast = useToast()
const statusSaving = ref(false)

interface ChecklistItem {
  key: string
  label: string
  detail: string
  done: boolean
  icon: string
  to: string
}

const storeSettings = computed(() => props.store.settings ?? {})
const pickupAllowed = computed(() => storeSettings.value.pay_on_pickup_allowed === true)
const onlinePaymentsReady = computed(() => props.paymentSettings?.accepts_online === true)

const items = computed<ChecklistItem[]>(() => [
  {
    key: 'profile',
    label: 'Store profile',
    detail: 'Name, slug, and pickup address are ready.',
    done: Boolean(props.store.name && props.store.slug && props.store.address),
    icon: 'store',
    to: '/stores',
  },
  {
    key: 'categories',
    label: 'Catalog groups',
    detail: 'Create at least one product type for customers to browse.',
    done: Number(props.store.product_types_count ?? 0) > 0,
    icon: 'layout-grid',
    to: `/stores/${props.store.id}/catalog`,
  },
  {
    key: 'products',
    label: 'Products and prices',
    detail: 'Add at least one active product with customer pricing.',
    done: Number(props.store.products_count ?? 0) > 0,
    icon: 'package',
    to: `/stores/${props.store.id}/catalog`,
  },
  {
    key: 'payment',
    label: 'Checkout method',
    detail: props.loadingPayment
      ? 'Checking payment setup.'
      : onlinePaymentsReady.value
        ? 'Online payments are connected.'
        : 'Enable pickup payment or connect online payments.',
    done: pickupAllowed.value || onlinePaymentsReady.value,
    icon: 'credit-card',
    to: `/stores/${props.store.id}/payments`,
  },
  {
    key: 'publish',
    label: 'Storefront live',
    detail: 'Set the store status to active when it is ready for customers.',
    done: props.store.status === 'active',
    icon: 'globe',
    to: '/stores',
  },
])

const completedCount = computed(() => items.value.filter((item) => item.done).length)
const progress = computed(() => Math.round((completedCount.value / items.value.length) * 100))
const nextItem = computed(() => items.value.find((item) => !item.done) ?? null)
const setupComplete = computed(() => items.value.filter((item) => item.key !== 'publish').every((item) => item.done))
const readyToShare = computed(() => completedCount.value === items.value.length)
const storefrontUrl = computed(() => props.store.slug ? `/s/${props.store.slug}` : '/stores')
const previewUrl = computed(() => `/stores/${props.store.id}/preview`)
const publicUrl = computed(() => new URL(storefrontUrl.value, window.location.origin).toString())
const statusMeta = computed(() => {
  if (props.store.status === 'active') {
    return { label: 'Live', variant: 'success' as const, detail: 'Customers can open the storefront and place orders.' }
  }
  if (props.store.status === 'suspended') {
    return { label: 'Suspended', variant: 'danger' as const, detail: 'The storefront is hidden from customers.' }
  }

  return { label: 'Trial', variant: 'neutral' as const, detail: 'The storefront is hidden until you publish it.' }
})

async function setStatus(status: Store['status']): Promise<void> {
  if (status === props.store.status || statusSaving.value) return

  if (status === 'active' && !setupComplete.value) {
    toast.warning('Finish the setup checklist before publishing.')
    return
  }

  statusSaving.value = true
  try {
    const store = await updateStore(props.store.id, { status })
    emit('updated', store)
    toast.success(status === 'active' ? 'Storefront published.' : 'Storefront status updated.')
  } catch {
    toast.error('Could not update storefront status.')
  } finally {
    statusSaving.value = false
  }
}

async function copyUrl(): Promise<void> {
  try {
    await navigator.clipboard.writeText(publicUrl.value)
    toast.success('Storefront URL copied.')
  } catch {
    toast.error('Could not copy the storefront URL.')
  }
}
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <p class="text-sm font-medium text-primary-700 dark:text-primary-300">Storefront launch</p>
          <AppBadge :variant="readyToShare ? 'success' : 'warning'" size="sm" dot>
            {{ readyToShare ? 'Ready' : `${completedCount}/${items.length} done` }}
          </AppBadge>
        </div>
        <h2 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
          Publish {{ store.name }}
        </h2>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
          Finish these setup steps before sending customers to the storefront.
        </p>
      </div>

      <RouterLink
        :to="readyToShare ? storefrontUrl : (nextItem?.to ?? '/stores')"
        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 sm:w-auto"
      >
        <AppIcon :name="readyToShare ? 'external-link' : 'arrow-right'" :size="16" />
        <span>{{ readyToShare ? 'View storefront' : `Continue: ${nextItem?.label}` }}</span>
      </RouterLink>
    </div>

    <div class="mt-4">
      <div class="flex items-center justify-between gap-3 text-xs font-medium text-slate-500 dark:text-slate-400">
        <span>Launch progress</span>
        <span>{{ progress }}%</span>
      </div>
      <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div
          class="h-full rounded-full bg-primary-600 transition-all dark:bg-primary-400"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>

    <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200 dark:bg-slate-950/40 dark:ring-slate-800 lg:grid-cols-[minmax(0,1fr)_auto]">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <AppBadge :variant="statusMeta.variant" size="sm" dot>{{ statusMeta.label }}</AppBadge>
          <span class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ publicUrl }}</span>
        </div>
        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ statusMeta.detail }}</p>
      </div>

      <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
        <button
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
          type="button"
          @click="copyUrl"
        >
          <AppIcon name="copy" :size="15" />
          <span>Copy</span>
        </button>
        <RouterLink
          :to="previewUrl"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
          <AppIcon name="eye" :size="15" />
          <span>Preview</span>
        </RouterLink>
        <button
          v-if="store.status !== 'active'"
          class="col-span-2 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50 sm:col-span-1"
          type="button"
          :disabled="statusSaving || !setupComplete"
          @click="setStatus('active')"
        >
          <AppIcon name="globe" :size="15" />
          <span>Publish</span>
        </button>
        <button
          v-else
          class="col-span-2 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:opacity-50 sm:col-span-1"
          type="button"
          :disabled="statusSaving"
          @click="setStatus('trial')"
        >
          <AppIcon name="pause-circle" :size="15" />
          <span>Unpublish</span>
        </button>
      </div>
    </div>

    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
      <RouterLink
        v-for="item in items"
        :key="item.key"
        :to="item.to"
        class="group rounded-xl border p-3 transition hover:-translate-y-0.5 hover:shadow-sm"
        :class="item.done
          ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20'
          : 'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/40'"
      >
        <div class="flex items-center justify-between gap-2">
          <span
            class="flex h-8 w-8 items-center justify-center rounded-lg"
            :class="item.done
              ? 'bg-white text-emerald-700 ring-1 ring-emerald-200 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-900'
              : 'bg-white text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-800'"
          >
            <AppIcon :name="item.done ? 'check' : item.icon" :size="16" />
          </span>
          <AppIcon name="chevron-right" :size="15" class="text-slate-400 transition group-hover:translate-x-0.5" />
        </div>
        <p class="mt-3 text-sm font-semibold text-slate-950 dark:text-white">{{ item.label }}</p>
        <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-400">{{ item.detail }}</p>
      </RouterLink>
    </div>
  </section>
</template>
