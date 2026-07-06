<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import { useStorefrontQuote } from '@/composables/useStorefrontQuote'
import { getErrorMessage } from '@/services/api'
import type { CartLine } from '@/composables/useStorefrontCart'
import type { StorefrontProduct } from '@/types/printly'
import { formatMoney } from '@/utils/money'

const props = defineProps<{
  slug: string
  product: StorefrontProduct
}>()

const emit = defineEmits<{
  back: []
  add: [line: Omit<CartLine, 'key'>]
  error: [message: string]
}>()

const {
  config,
  currentFile,
  uploadToken,
  uploading,
  quote,
  quoting,
  isFileBased,
  pageCount,
  analysisPending,
  hasDuplexRule,
  paperSizes,
  setProduct,
  uploadFile,
  stopAnalysisPolling,
} = useStorefrontQuote(props.slug, error => emit('error', getErrorMessage(error)))

const canAdd = computed(() => Boolean(quote.value) && (!isFileBased.value || Boolean(pageCount.value)))

onMounted(() => setProduct(props.product))
onUnmounted(() => stopAnalysisPolling())

async function onFileChange(e: Event): Promise<void> {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  try {
    await uploadFile(file)
  } catch (error) {
    emit('error', getErrorMessage(error))
  } finally {
    input.value = ''
  }
}

function addToCart(): void {
  if (!quote.value) return

  if (isFileBased.value) {
    const parts = [`${pageCount.value} pp`, config.color === 'color' ? 'Color' : 'B&W']
    if (config.paper_size) parts.push(config.paper_size)
    if (hasDuplexRule.value && config.duplex) parts.push('Duplex')
    parts.push(`x${config.copies}`)

    emit('add', {
      productName: props.product.name,
      summary: parts.join(' - '),
      totalCents: quote.value.total_cents,
      item: {
        product_id: props.product.id,
        file_ids: currentFile.value ? [currentFile.value.id] : [],
        file_tokens: currentFile.value && uploadToken.value ? { [currentFile.value.id]: uploadToken.value } : undefined,
        spec: {
          page_count: pageCount.value ?? 1,
          color: config.color,
          copies: config.copies,
          ...(config.paper_size ? { paper_size: config.paper_size } : {}),
          ...(hasDuplexRule.value ? { duplex: config.duplex } : {}),
        },
      },
    })
    return
  }

  const selections = Object.entries(config.selections).map(([option_id, choice]) => ({ option_id, choice }))
  emit('add', {
    productName: props.product.name,
    summary: `${selections.map(selection => selection.choice).join(', ') || 'Standard'} - x${config.quantity}`,
    totalCents: quote.value.total_cents,
    item: { product_id: props.product.id, quantity: config.quantity, selections },
  })
}
</script>

<template>
  <div class="min-h-[calc(100dvh-2rem)]">
    <header class="sticky top-0 z-10 -mx-4 -mt-4 border-b border-slate-200 bg-slate-50/95 px-4 pb-3 pt-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <button class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-500" @click="emit('back')">
        <AppIcon name="arrow-left" :size="17" /> Back
      </button>
      <div class="flex items-start gap-3">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
          <AppIcon :name="isFileBased ? 'file-text' : 'layers'" :size="24" />
        </div>
        <div class="min-w-0">
          <h2 class="font-display text-2xl font-bold leading-tight text-slate-950 dark:text-white">{{ product.name }}</h2>
          <p class="mt-1 text-sm text-slate-500">{{ isFileBased ? 'Upload a file and confirm print settings.' : 'Pick options and quantity.' }}</p>
        </div>
      </div>
    </header>

    <template v-if="isFileBased">
      <div class="mt-4 rounded-3xl border border-dashed border-blue-200 bg-white p-4 text-center shadow-sm dark:border-blue-900/60 dark:bg-zinc-900">
        <label class="block cursor-pointer">
          <input type="file" accept=".pdf,image/*" class="hidden" @change="onFileChange">
          <div class="flex flex-col items-center py-6">
            <AppSpinner v-if="uploading" size="md" />
            <div v-else class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50">
              <AppIcon name="upload" :size="28" />
            </div>
            <span class="mt-3 text-base font-semibold text-blue-600">
              {{ currentFile ? 'Replace file' : 'Upload your file' }}
            </span>
            <span class="mt-1 text-sm text-slate-400">PDF or image, up to 50 MB</span>
          </div>
        </label>
      </div>

      <div v-if="currentFile" class="mt-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center gap-2">
          <AppIcon name="file-text" :size="18" class="text-blue-600" />
          <span class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ currentFile.original_name }}</span>
        </div>
        <p class="mt-2 text-sm" :class="currentFile.analysis_status === 'failed' ? 'text-red-500' : 'text-slate-500'">
          <span v-if="analysisPending">Analysing... counting pages</span>
          <span v-else-if="currentFile.analysis_status === 'failed'">Could not read this file. Enter page count manually.</span>
          <span v-else>{{ currentFile.page_count }} page(s){{ currentFile.paper_size ? ` - ${currentFile.paper_size}` : '' }}</span>
        </p>
      </div>

      <div v-if="currentFile && currentFile.analysis_status === 'failed'" class="mt-4">
        <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Pages</label>
        <input
          type="number"
          min="1"
          class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-900"
          :value="currentFile.page_count ?? ''"
          @input="currentFile && (currentFile.page_count = Number(($event.target as HTMLInputElement).value) || null)"
        >
      </div>

      <div class="mt-4 grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Color</label>
          <div class="mt-1 flex rounded-2xl border border-slate-300 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900">
            <button
              v-for="color in (['bw', 'color'] as const)"
              :key="color"
              class="min-h-10 flex-1 rounded-xl text-sm font-semibold transition"
              :class="config.color === color ? 'bg-blue-600 text-white' : 'text-slate-500'"
              @click="config.color = color"
            >
              {{ color === 'bw' ? 'B&W' : 'Color' }}
            </button>
          </div>
        </div>
        <div>
          <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Copies</label>
          <input v-model.number="config.copies" type="number" min="1" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-900">
        </div>
        <div v-if="paperSizes.length">
          <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Paper size</label>
          <select v-model="config.paper_size" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">Default</option>
            <option v-for="size in paperSizes" :key="size" :value="size">{{ size }}</option>
          </select>
        </div>
        <label v-if="hasDuplexRule" class="flex min-h-12 items-center gap-2 self-end rounded-2xl border border-slate-300 bg-white px-4 dark:border-zinc-700 dark:bg-zinc-900">
          <input v-model="config.duplex" type="checkbox" class="h-4 w-4 rounded">
          <span class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Double-sided</span>
        </label>
      </div>
    </template>

    <template v-else>
      <div v-for="option in product.options" :key="option.id" class="mt-4">
        <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">{{ option.name }}</label>
        <select v-model="config.selections[option.id]" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-900">
          <option v-for="choice in option.choices" :key="choice.label" :value="choice.label">
            {{ choice.label }}{{ choice.price_delta_cents ? ` (+${formatMoney(choice.price_delta_cents)})` : '' }}
          </option>
        </select>
      </div>
      <div class="mt-4">
        <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Quantity</label>
        <input v-model.number="config.quantity" type="number" min="1" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-900">
      </div>
    </template>

    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[480px] border-t border-slate-200 bg-white/95 px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Estimated price</span>
        <span class="text-xl font-bold text-slate-950 dark:text-white">
          <AppSpinner v-if="quoting" size="sm" />
          <template v-else-if="quote">{{ formatMoney(quote.total_cents) }}</template>
          <template v-else>-</template>
        </span>
      </div>
      <button
        class="w-full rounded-2xl bg-blue-600 py-3 font-semibold text-white shadow-xl shadow-blue-200 transition disabled:cursor-not-allowed disabled:opacity-50 dark:shadow-blue-950/40"
        :disabled="!canAdd"
        @click="addToCart"
      >
        Add to cart
      </button>
      <p v-if="isFileBased && !currentFile" class="mt-2 text-center text-xs text-slate-400">Upload a file to see the price.</p>
    </div>
  </div>
</template>
