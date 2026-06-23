<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDebounceFn, useIntervalFn } from '@vueuse/core'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import { useToast } from '@/composables/useToast'
import { ensureCsrfCookie, getErrorMessage } from '@/services/api'
import {
  getStorefront,
  getStorefrontFile,
  placeStorefrontOrder,
  quoteStorefront,
  uploadStorefrontFile,
  type PlaceOrderItem,
} from '@/services/storefrontService'
import type {
  OrderFile,
  Quote,
  StorefrontCatalog,
  StorefrontProduct,
} from '@/types/printly'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const slug = String(route.params.slug)

type View = 'catalog' | 'configure' | 'cart' | 'checkout'
const view = ref<View>('catalog')

const catalog = ref<StorefrontCatalog | null>(null)
const loading = ref(true)
const errorMessage = ref('')

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

// ── Cart ────────────────────────────────────────────────────────────────────
interface CartLine {
  key: string
  productName: string
  summary: string
  totalCents: number
  item: PlaceOrderItem
}
const cart = ref<CartLine[]>([])
const cartTotal = computed(() => cart.value.reduce((sum, l) => sum + l.totalCents, 0))

function removeLine(key: string): void {
  cart.value = cart.value.filter(l => l.key !== key)
  if (cart.value.length === 0) view.value = 'catalog'
}

// ── Configure a product ───────────────────────────────────────────────────────
const active = ref<StorefrontProduct | null>(null)
const config = reactive({
  copies: 1,
  color: 'bw' as 'bw' | 'color',
  paper_size: '' as string,
  duplex: false,
  quantity: 1,
  selections: {} as Record<string, string>, // option_id -> choice label
})
const currentFile = ref<OrderFile | null>(null)
const uploading = ref(false)
const quote = ref<Quote | null>(null)
const quoting = ref(false)

const isFileBased = computed(() => active.value?.pricing_mode === 'file_based')

const paperSizes = computed(() => {
  if (!active.value) return [] as string[]
  const sizes = active.value.price_rules
    .filter(r => r.attribute === 'paper_size')
    .map(r => r.match_value)
  const detected = currentFile.value?.paper_size
  if (detected && !sizes.includes(detected)) sizes.unshift(detected)
  return [...new Set(sizes)]
})
const hasDuplexRule = computed(() => active.value?.price_rules.some(r => r.attribute === 'duplex') ?? false)
const pageCount = computed(() => currentFile.value?.page_count ?? null)
const analysisPending = computed(() => currentFile.value?.analysis_status === 'pending')

function openConfigure(product: StorefrontProduct): void {
  active.value = product
  currentFile.value = null
  quote.value = null
  config.copies = 1
  config.color = 'bw'
  config.paper_size = ''
  config.duplex = false
  config.quantity = 1
  config.selections = {}
  // Pre-select the first choice of each option for spec_based.
  if (product.pricing_mode === 'spec_based') {
    for (const opt of product.options) {
      if (opt.choices[0]) config.selections[opt.id] = opt.choices[0].label
    }
  }
  view.value = 'configure'
}

// File upload + analysis polling
async function onFileChange(e: Event): Promise<void> {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  uploading.value = true
  try {
    currentFile.value = await uploadStorefrontFile(slug, file)
    if (analysisPending.value) startAnalysisPolling()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    uploading.value = false
    input.value = ''
  }
}

const { pause: stopAnalysisPolling, resume: resumeAnalysisPolling, isActive: polling } = useIntervalFn(async () => {
  if (!currentFile.value) return
  try {
    currentFile.value = await getStorefrontFile(slug, currentFile.value.id)
    if (currentFile.value.analysis_status !== 'pending') {
      stopAnalysisPolling()
      refreshQuote()
    }
  } catch {
    stopAnalysisPolling()
  }
}, 2000, { immediate: false })

function startAnalysisPolling(): void {
  if (!polling.value) resumeAnalysisPolling()
}

// Live quote
function buildQuotePayload(): Record<string, unknown> | null {
  if (!active.value) return null
  if (isFileBased.value) {
    if (!pageCount.value) return null
    return {
      product_id: active.value.id,
      page_count: pageCount.value,
      color: config.color,
      copies: config.copies,
      ...(config.paper_size ? { paper_size: config.paper_size } : {}),
      ...(hasDuplexRule.value ? { duplex: config.duplex } : {}),
    }
  }
  return {
    product_id: active.value.id,
    quantity: config.quantity,
    selections: Object.entries(config.selections).map(([option_id, choice]) => ({ option_id, choice })),
  }
}

const refreshQuote = useDebounceFn(async () => {
  const payload = buildQuotePayload()
  if (!payload) {
    quote.value = null
    return
  }
  quoting.value = true
  try {
    quote.value = await quoteStorefront(slug, payload as never)
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    quoting.value = false
  }
}, 350)

watch(config, () => refreshQuote(), { deep: true })

function addToCart(): void {
  if (!active.value || !quote.value) return
  const product = active.value

  let summary: string
  let item: PlaceOrderItem

  if (isFileBased.value) {
    const parts = [`${pageCount.value} pp`, config.color === 'color' ? 'Color' : 'B&W']
    if (config.paper_size) parts.push(config.paper_size)
    if (hasDuplexRule.value && config.duplex) parts.push('Duplex')
    parts.push(`×${config.copies}`)
    summary = parts.join(' · ')
    item = {
      product_id: product.id,
      file_ids: currentFile.value ? [currentFile.value.id] : [],
      spec: {
        page_count: pageCount.value ?? 1,
        color: config.color,
        copies: config.copies,
        ...(config.paper_size ? { paper_size: config.paper_size } : {}),
        ...(hasDuplexRule.value ? { duplex: config.duplex } : {}),
      },
    }
  } else {
    const selections = Object.entries(config.selections).map(([option_id, choice]) => ({ option_id, choice }))
    summary = `${selections.map(s => s.choice).join(', ') || 'Standard'} · ×${config.quantity}`
    item = { product_id: product.id, quantity: config.quantity, selections }
  }

  cart.value.push({
    key: `${product.id}-${Date.now()}`,
    productName: product.name,
    summary,
    totalCents: quote.value.total_cents,
    item,
  })
  toast.success('Added to cart')
  view.value = 'catalog'
}

// ── Checkout ──────────────────────────────────────────────────────────────────
const customer = reactive({ name: '', phone: '', email: '' })
const payMethod = ref('cash_on_pickup')
const notes = ref('')
const placing = ref(false)

const payOnPickupAllowed = computed(() => catalog.value?.store.settings.pay_on_pickup_allowed ?? false)

async function placeOrder(): Promise<void> {
  if (!customer.phone && !customer.email) {
    toast.error('Add a phone number or email so the store can reach you.')
    return
  }
  placing.value = true
  try {
    await ensureCsrfCookie()
    const order = await placeStorefrontOrder(slug, {
      customer: { name: customer.name || undefined, phone: customer.phone || undefined, email: customer.email || undefined },
      pay_method: payMethod.value,
      notes: notes.value || undefined,
      items: cart.value.map(l => l.item),
    })
    cart.value = []
    router.push({ name: 'order-status', params: { code: order.code } })
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    placing.value = false
  }
}

onMounted(async () => {
  try {
    // Prime the CSRF cookie so guest POSTs (upload/order) are accepted.
    ensureCsrfCookie().catch(() => {})
    catalog.value = await getStorefront(slug)
  } catch (err) {
    errorMessage.value = getErrorMessage(err)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="py-2">
    <div v-if="loading" class="flex justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="errorMessage" class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-zinc-800 dark:bg-zinc-900">
      <AppIcon name="store" :size="40" class="mx-auto text-gray-400" />
      <p class="mt-3 font-medium text-gray-900 dark:text-white">Store unavailable</p>
      <p class="mt-1 text-sm text-gray-500">{{ errorMessage }}</p>
    </div>

    <template v-else-if="catalog">
      <!-- Header -->
      <header class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ catalog.store.name }}</h1>
          <p v-if="catalog.store.address" class="text-sm text-gray-500">{{ catalog.store.address }}</p>
        </div>
        <button
          v-if="cart.length"
          class="relative rounded-full bg-primary-600 p-2.5 text-white"
          @click="view = 'cart'"
        >
          <AppIcon name="shopping-cart" :size="20" />
          <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs">{{ cart.length }}</span>
        </button>
      </header>

      <!-- ── Catalog ─────────────────────────────────────────────────── -->
      <div v-if="view === 'catalog'" class="space-y-6">
        <section v-for="type in catalog.product_types" :key="type.id">
          <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-400">{{ type.name }}</h2>
          <div class="space-y-2">
            <button
              v-for="product in type.products"
              :key="product.id"
              class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white p-4 text-left transition hover:border-primary-400 dark:border-zinc-800 dark:bg-zinc-900"
              @click="openConfigure(product)"
            >
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                <p class="text-sm text-gray-500">
                  {{ product.pricing_mode === 'file_based' ? 'From' : '' }} {{ formatMoney(product.base_price_cents) }}
                  {{ product.pricing_mode === 'file_based' ? '/ page' : '' }}
                </p>
              </div>
              <AppIcon name="chevron-right" :size="20" class="text-gray-400" />
            </button>
          </div>
          <p v-if="!type.products.length" class="text-sm text-gray-400">No products yet.</p>
        </section>

        <div v-if="!catalog.product_types.length" class="py-16 text-center text-gray-400">
          This store hasn’t published any products yet.
        </div>
      </div>

      <!-- ── Configure ───────────────────────────────────────────────── -->
      <div v-else-if="view === 'configure' && active" class="space-y-4">
        <button class="flex items-center gap-1 text-sm text-gray-500" @click="view = 'catalog'">
          <AppIcon name="arrow-left" :size="16" /> Back
        </button>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ active.name }}</h2>

        <!-- file_based -->
        <template v-if="isFileBased">
          <div class="rounded-xl border border-dashed border-gray-300 bg-white p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <label class="block cursor-pointer">
              <input type="file" accept=".pdf,image/*" class="hidden" @change="onFileChange">
              <div class="flex flex-col items-center py-4">
                <AppSpinner v-if="uploading" size="md" />
                <AppIcon v-else name="upload" :size="28" class="text-gray-400" />
                <span class="mt-2 text-sm font-medium text-primary-600">
                  {{ currentFile ? 'Replace file' : 'Upload your file' }}
                </span>
                <span class="text-xs text-gray-400">PDF or image, up to 50 MB</span>
              </div>
            </label>
          </div>

          <div v-if="currentFile" class="rounded-xl border border-gray-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center gap-2">
              <AppIcon name="file-text" :size="18" class="text-gray-400" />
              <span class="truncate text-sm text-gray-900 dark:text-white">{{ currentFile.original_name }}</span>
            </div>
            <p class="mt-1 text-xs" :class="currentFile.analysis_status === 'failed' ? 'text-red-500' : 'text-gray-500'">
              <span v-if="analysisPending">Analysing… counting pages</span>
              <span v-else-if="currentFile.analysis_status === 'failed'">Couldn’t read this file — enter page count manually</span>
              <span v-else>{{ currentFile.page_count }} page(s){{ currentFile.paper_size ? ` · ${currentFile.paper_size}` : '' }}</span>
            </p>
          </div>

          <!-- page count fallback when analysis fails -->
          <div v-if="currentFile && currentFile.analysis_status === 'failed'">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Pages</label>
            <input
              type="number" min="1"
              class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
              :value="currentFile.page_count ?? ''"
              @input="currentFile && (currentFile.page_count = Number(($event.target as HTMLInputElement).value) || null)"
            >
          </div>

          <!-- options -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Color</label>
              <div class="mt-1 flex rounded-lg border border-gray-300 p-0.5 dark:border-zinc-700">
                <button
                  v-for="c in (['bw', 'color'] as const)" :key="c"
                  class="flex-1 rounded-md py-1.5 text-sm font-medium transition"
                  :class="config.color === c ? 'bg-primary-600 text-white' : 'text-gray-500'"
                  @click="config.color = c"
                >{{ c === 'bw' ? 'B&W' : 'Color' }}</button>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Copies</label>
              <input
                v-model.number="config.copies" type="number" min="1"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
              >
            </div>
            <div v-if="paperSizes.length">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Paper size</label>
              <select
                v-model="config.paper_size"
                class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
              >
                <option value="">Default</option>
                <option v-for="s in paperSizes" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <label v-if="hasDuplexRule" class="flex items-center gap-2 self-end pb-2">
              <input v-model="config.duplex" type="checkbox" class="h-4 w-4 rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Double-sided</span>
            </label>
          </div>
        </template>

        <!-- spec_based -->
        <template v-else>
          <div v-for="opt in active.options" :key="opt.id">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ opt.name }}</label>
            <select
              v-model="config.selections[opt.id]"
              class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
              <option v-for="choice in opt.choices" :key="choice.label" :value="choice.label">
                {{ choice.label }}
                <template v-if="choice.price_delta_cents">(+{{ formatMoney(choice.price_delta_cents) }})</template>
              </option>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
            <input
              v-model.number="config.quantity" type="number" min="1"
              class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
          </div>
        </template>

        <!-- live quote + add -->
        <div class="sticky bottom-4 rounded-xl border border-gray-200 bg-white p-4 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
          <div class="mb-3 flex items-center justify-between">
            <span class="text-sm text-gray-500">Price</span>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">
              <AppSpinner v-if="quoting" size="sm" />
              <template v-else-if="quote">{{ formatMoney(quote.total_cents) }}</template>
              <template v-else>—</template>
            </span>
          </div>
          <button
            class="w-full rounded-lg bg-primary-600 py-3 font-medium text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!quote || (isFileBased && !pageCount)"
            @click="addToCart"
          >
            Add to cart
          </button>
          <p v-if="isFileBased && !currentFile" class="mt-2 text-center text-xs text-gray-400">Upload a file to see the price.</p>
        </div>
      </div>

      <!-- ── Cart ────────────────────────────────────────────────────── -->
      <div v-else-if="view === 'cart'" class="space-y-4">
        <button class="flex items-center gap-1 text-sm text-gray-500" @click="view = 'catalog'">
          <AppIcon name="arrow-left" :size="16" /> Keep shopping
        </button>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your order</h2>

        <div class="space-y-2">
          <div
            v-for="line in cart" :key="line.key"
            class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
          >
            <div class="min-w-0">
              <p class="font-medium text-gray-900 dark:text-white">{{ line.productName }}</p>
              <p class="truncate text-sm text-gray-500">{{ line.summary }}</p>
            </div>
            <div class="flex items-center gap-3 pl-3">
              <span class="font-semibold text-gray-900 dark:text-white">{{ formatMoney(line.totalCents) }}</span>
              <button class="text-gray-400 hover:text-red-500" @click="removeLine(line.key)">
                <AppIcon name="trash" :size="18" />
              </button>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between rounded-xl bg-gray-100 px-4 py-3 dark:bg-zinc-800">
          <span class="font-medium text-gray-700 dark:text-gray-200">Total</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(cartTotal) }}</span>
        </div>

        <button class="w-full rounded-lg bg-primary-600 py-3 font-medium text-white hover:bg-primary-700" @click="view = 'checkout'">
          Checkout
        </button>
      </div>

      <!-- ── Checkout ────────────────────────────────────────────────── -->
      <div v-else-if="view === 'checkout'" class="space-y-4">
        <button class="flex items-center gap-1 text-sm text-gray-500" @click="view = 'cart'">
          <AppIcon name="arrow-left" :size="16" /> Back to cart
        </button>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your details</h2>

        <div class="space-y-3">
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input v-model="customer.name" type="text" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
            <input v-model="customer.phone" type="tel" inputmode="tel" placeholder="0917…" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-gray-400">(optional)</span></label>
            <input v-model="customer.email" type="email" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
          </div>
          <div v-if="payOnPickupAllowed">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Payment</label>
            <select v-model="payMethod" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
              <option value="cash_on_pickup">Pay at pickup</option>
              <option value="gcash">GCash</option>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Notes <span class="text-gray-400">(optional)</span></label>
            <textarea v-model="notes" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"></textarea>
          </div>
        </div>

        <div class="flex items-center justify-between rounded-xl bg-gray-100 px-4 py-3 dark:bg-zinc-800">
          <span class="font-medium text-gray-700 dark:text-gray-200">Total</span>
          <span class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(cartTotal) }}</span>
        </div>

        <button
          class="w-full rounded-lg bg-primary-600 py-3 font-medium text-white hover:bg-primary-700 disabled:opacity-50"
          :disabled="placing"
          @click="placeOrder"
        >
          <AppSpinner v-if="placing" size="sm" />
          <span v-else>Place order</span>
        </button>
      </div>
    </template>
  </div>
</template>
