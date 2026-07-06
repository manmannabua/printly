<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import CartSummary from '@/components/storefront/CartSummary.vue'
import CheckoutForm from '@/components/storefront/CheckoutForm.vue'
import ProductConfigurator from '@/components/storefront/ProductConfigurator.vue'
import { useStorefrontCart, type CartLine } from '@/composables/useStorefrontCart'
import { useToast } from '@/composables/useToast'
import { ensureCsrfCookie, getErrorMessage } from '@/services/api'
import { getStorefront, getStorefrontPreview, placeStorefrontOrder } from '@/services/storefrontService'
import type { StorefrontCatalog, StorefrontProduct } from '@/types/printly'
import { formatMoney } from '@/utils/money'

type View = 'catalog' | 'configure' | 'cart' | 'checkout'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const isPreview = computed(() => route.name === 'storefront-preview')
const slug = computed(() => String(route.params.slug ?? catalog.value?.store.slug ?? ''))
const storeId = computed(() => String(route.params.id ?? ''))

const view = ref<View>('catalog')
const catalog = ref<StorefrontCatalog | null>(null)
const activeProduct = ref<StorefrontProduct | null>(null)
const activeTypeId = ref<string | null>(null)
const loading = ref(true)
const placing = ref(false)
const errorMessage = ref('')

const { cart, cartTotal, addLine, removeLine, clear } = useStorefrontCart()
const payOnPickupAllowed = computed(() => catalog.value?.store.settings.pay_on_pickup_allowed ?? false)
const acceptsOnlinePayments = computed(() => catalog.value?.store.settings.accepts_online_payments ?? false)
const activeType = computed(() => catalog.value?.product_types.find(type => type.id === activeTypeId.value) ?? null)
const activeProducts = computed(() => activeType.value?.products ?? [])
const totalProducts = computed(() => catalog.value?.product_types.reduce((sum, type) => sum + type.products.length, 0) ?? 0)
const paymentLabel = computed(() => {
  if (payOnPickupAllowed.value && acceptsOnlinePayments.value) return 'Pickup or online'
  if (acceptsOnlinePayments.value) return 'Online payment'
  if (payOnPickupAllowed.value) return 'Pay at pickup'
  return 'Payments unavailable'
})

function openConfigure(product: StorefrontProduct): void {
  if (isPreview.value) {
    toast.info('Preview is browse-only. Publish the store before taking test orders.')
    return
  }

  activeProduct.value = product
  view.value = 'configure'
}

function onConfigured(line: Omit<CartLine, 'key'>): void {
  addLine(line)
  toast.success('Added to cart')
  view.value = 'catalog'
}

function onRemoveLine(key: string): void {
  removeLine(key)
  if (cart.value.length === 0) view.value = 'catalog'
}

async function placeOrder(payload: {
  customer: { name?: string, phone?: string, email?: string }
  pay_method: string
  notes?: string
}): Promise<void> {
  if (isPreview.value) {
    toast.info('Preview mode does not place orders.')
    return
  }

  if (!payload.customer.phone && !payload.customer.email) {
    toast.error('Add a phone number or email so the store can reach you.')
    return
  }

  placing.value = true
  try {
    await ensureCsrfCookie()
    const order = await placeStorefrontOrder(slug.value, {
      ...payload,
      items: cart.value.map(line => line.item),
    })
    clear()

    if (order.checkout_url) {
      window.location.href = order.checkout_url
      return
    }

    router.push({ name: 'order-status', params: { code: order.code } })
  } catch (error) {
    toast.error(getErrorMessage(error))
  } finally {
    placing.value = false
  }
}

onMounted(async () => {
  try {
    ensureCsrfCookie().catch(() => {})
    catalog.value = isPreview.value
      ? await getStorefrontPreview(storeId.value)
      : await getStorefront(slug.value)
    activeTypeId.value = catalog.value.product_types[0]?.id ?? null
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    loading.value = false
  }
})

watch(catalog, (value) => {
  if (value && !activeTypeId.value) {
    activeTypeId.value = value.product_types[0]?.id ?? null
  }
})
</script>

<template>
  <div class="min-h-[calc(100dvh-2rem)]">
    <div v-if="loading" class="flex min-h-[70dvh] flex-col items-center justify-center gap-3">
      <AppSpinner size="lg" />
      <p class="text-sm text-slate-500 dark:text-zinc-400">Loading storefront...</p>
    </div>

    <div v-else-if="errorMessage" class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-zinc-800">
        <AppIcon name="store" :size="30" />
      </div>
      <p class="mt-4 font-semibold text-slate-950 dark:text-white">Store unavailable</p>
      <p class="mt-1 text-sm text-slate-500">{{ errorMessage }}</p>
    </div>

    <template v-else-if="catalog">
      <div
        v-if="isPreview"
        class="sticky top-0 z-30 -mx-4 -mt-4 border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 dark:border-amber-900/60 dark:bg-amber-950 dark:text-amber-100"
      >
        <div class="mx-auto flex max-w-[480px] items-center gap-2">
          <AppIcon name="eye" :size="17" />
          <span class="min-w-0 flex-1">Preview mode. Customers cannot see this page until the store is active.</span>
        </div>
      </div>

      <template v-if="view === 'catalog'">
        <header
          class="sticky z-20 -mx-4 border-b border-slate-200/80 bg-slate-50/95 px-4 pb-3 pt-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95"
          :class="isPreview ? 'top-11' : 'top-0 -mt-4'"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="mb-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Open for orders
              </div>
              <h1 class="truncate font-display text-2xl font-bold text-slate-950 dark:text-white">{{ catalog.store.name }}</h1>
              <p v-if="catalog.store.address" class="mt-1 truncate text-sm text-slate-500">{{ catalog.store.address }}</p>
            </div>
            <button
              class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-950 text-white shadow-lg shadow-slate-300 disabled:opacity-40 dark:bg-white dark:text-zinc-950 dark:shadow-black/30"
              :disabled="!cart.length || isPreview"
              aria-label="Open cart"
              @click="view = 'cart'"
            >
              <AppIcon name="shopping-cart" :size="20" />
              <span v-if="cart.length" class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-600 px-1 text-xs font-bold text-white">{{ cart.length }}</span>
            </button>
          </div>
          <div class="mt-4 grid grid-cols-3 gap-2">
            <div class="rounded-2xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 dark:bg-zinc-900 dark:ring-zinc-800">
              <p class="text-[11px] font-medium text-slate-400">Items</p>
              <p class="text-sm font-bold text-slate-950 dark:text-white">{{ totalProducts }}</p>
            </div>
            <div class="rounded-2xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 dark:bg-zinc-900 dark:ring-zinc-800">
              <p class="text-[11px] font-medium text-slate-400">Payment</p>
              <p class="truncate text-sm font-bold text-slate-950 dark:text-white">{{ paymentLabel }}</p>
            </div>
            <div class="rounded-2xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-200 dark:bg-zinc-900 dark:ring-zinc-800">
              <p class="text-[11px] font-medium text-slate-400">Cart</p>
              <p class="text-sm font-bold text-slate-950 dark:text-white">{{ formatMoney(cartTotal) }}</p>
            </div>
          </div>
        </header>

        <div v-if="catalog.product_types.length" class="mt-4">
          <div class="scrollbar-hover -mx-4 flex gap-2 overflow-x-auto px-4 pb-2">
            <button
              v-for="type in catalog.product_types"
              :key="type.id"
              class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition"
              :class="activeTypeId === type.id
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 dark:shadow-blue-950/40'
                : 'bg-white text-slate-600 ring-1 ring-slate-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-800'"
              @click="activeTypeId = type.id"
            >
              {{ type.name }}
            </button>
          </div>

          <section class="mt-3 space-y-3">
            <button
              v-for="product in activeProducts"
              :key="product.id"
              class="group flex w-full items-center gap-3 rounded-3xl border border-slate-200 bg-white p-3 text-left shadow-sm transition active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 dark:border-zinc-800 dark:bg-zinc-900"
              :disabled="isPreview"
              @click="openConfigure(product)"
            >
              <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
                <AppIcon :name="product.pricing_mode === 'file_based' ? 'file-text' : 'layers'" :size="24" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-slate-950 dark:text-white">{{ product.name }}</p>
                <p class="mt-0.5 text-sm text-slate-500">
                  {{ product.pricing_mode === 'file_based' ? 'Starts at' : 'From' }} {{ formatMoney(product.base_price_cents) }}
                  {{ product.pricing_mode === 'file_based' ? '/ page' : '' }}
                </p>
              </div>
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-active:bg-blue-600 group-active:text-white dark:bg-zinc-800">
                <AppIcon :name="isPreview ? 'eye' : 'chevron-right'" :size="18" />
              </div>
            </button>

            <div v-if="activeType && !activeProducts.length" class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 dark:border-zinc-700 dark:bg-zinc-900">
              No products in this category yet.
            </div>
          </section>
        </div>

        <div v-else class="py-20 text-center">
          <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-zinc-900">
            <AppIcon name="package" :size="32" />
          </div>
          <p class="mt-4 font-semibold text-slate-800 dark:text-zinc-100">No products yet</p>
          <p class="mt-1 text-sm text-slate-400">This store has not published its catalog.</p>
        </div>

        <div
          v-if="cart.length"
          class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[480px] border-t border-slate-200 bg-white/95 px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95"
        >
          <button
            class="flex w-full items-center justify-between rounded-2xl bg-slate-950 px-4 py-3 text-white shadow-xl shadow-slate-300 dark:bg-white dark:text-zinc-950 dark:shadow-black/40"
            @click="view = 'cart'"
          >
            <span class="text-left">
              <span class="block text-xs text-white/70 dark:text-zinc-500">{{ cart.length }} item{{ cart.length === 1 ? '' : 's' }}</span>
              <span class="font-semibold">Review order</span>
            </span>
            <span class="flex items-center gap-2 font-bold">
              {{ formatMoney(cartTotal) }}
              <AppIcon name="arrow-right" :size="18" />
            </span>
          </button>
        </div>
      </template>

      <ProductConfigurator
        v-else-if="view === 'configure' && activeProduct"
        :key="activeProduct.id"
        :slug="slug"
        :product="activeProduct"
        @back="view = 'catalog'"
        @add="onConfigured"
        @error="toast.error"
      />

      <CartSummary
        v-else-if="view === 'cart'"
        :lines="cart"
        :total-cents="cartTotal"
        @keep-shopping="view = 'catalog'"
        @checkout="view = 'checkout'"
        @remove="onRemoveLine"
      />

      <CheckoutForm
        v-else-if="view === 'checkout'"
        :total-cents="cartTotal"
        :pay-on-pickup-allowed="payOnPickupAllowed"
        :accepts-online-payments="acceptsOnlinePayments"
        :placing="placing"
        @back="view = 'cart'"
        @submit="placeOrder"
      />
    </template>
  </div>
</template>
