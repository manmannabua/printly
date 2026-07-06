import { computed, reactive, ref, watch } from 'vue'
import { useDebounceFn, useIntervalFn } from '@vueuse/core'
import {
  getStorefrontFile,
  quoteStorefront,
  uploadStorefrontFile,
  type StorefrontQuotePayload,
} from '@/services/storefrontService'
import type { OrderFile, Quote, StorefrontProduct } from '@/types/printly'

export function useStorefrontQuote(slug: string, onError: (message: unknown) => void) {
  const active = ref<StorefrontProduct | null>(null)
  const config = reactive({
    copies: 1,
    color: 'bw' as 'bw' | 'color',
    paper_size: '',
    duplex: false,
    quantity: 1,
    selections: {} as Record<string, string>,
  })
  const currentFile = ref<OrderFile | null>(null)
  const uploadToken = ref<string | null>(null)
  const uploading = ref(false)
  const quote = ref<Quote | null>(null)
  const quoting = ref(false)

  const isFileBased = computed(() => active.value?.pricing_mode === 'file_based')
  const pageCount = computed(() => currentFile.value?.page_count ?? null)
  const analysisPending = computed(() => currentFile.value?.analysis_status === 'pending')
  const hasDuplexRule = computed(() => active.value?.price_rules.some(rule => rule.attribute === 'duplex') ?? false)
  const paperSizes = computed(() => {
    if (!active.value) return [] as string[]

    const sizes = active.value.price_rules
      .filter(rule => rule.attribute === 'paper_size')
      .map(rule => rule.match_value)
    const detected = currentFile.value?.paper_size
    if (detected && !sizes.includes(detected)) sizes.unshift(detected)

    return [...new Set(sizes)]
  })

  const { pause: stopAnalysisPolling, resume: resumeAnalysisPolling, isActive: polling } = useIntervalFn(async () => {
    if (!currentFile.value || !uploadToken.value) return

    try {
      currentFile.value = await getStorefrontFile(slug, currentFile.value.id, uploadToken.value)
      if (currentFile.value.analysis_status !== 'pending') {
        stopAnalysisPolling()
        refreshQuote()
      }
    } catch (error) {
      stopAnalysisPolling()
      onError(error)
    }
  }, 2000, { immediate: false })

  function setProduct(product: StorefrontProduct): void {
    active.value = product
    currentFile.value = null
    uploadToken.value = null
    quote.value = null
    config.copies = 1
    config.color = 'bw'
    config.paper_size = ''
    config.duplex = false
    config.quantity = 1
    config.selections = {}

    if (product.pricing_mode === 'spec_based') {
      for (const option of product.options) {
        if (option.choices[0]) config.selections[option.id] = option.choices[0].label
      }
    }
  }

  async function uploadFile(file: File): Promise<void> {
    uploading.value = true

    try {
      currentFile.value = await uploadStorefrontFile(slug, file)
      uploadToken.value = currentFile.value.upload_token ?? null
      if (analysisPending.value && uploadToken.value && !polling.value) {
        resumeAnalysisPolling()
      } else {
        refreshQuote()
      }
    } finally {
      uploading.value = false
    }
  }

  function buildQuotePayload(): StorefrontQuotePayload | null {
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
      quote.value = await quoteStorefront(slug, payload)
    } catch (error) {
      onError(error)
    } finally {
      quoting.value = false
    }
  }, 350)

  watch(config, () => refreshQuote(), { deep: true })
  watch(pageCount, () => refreshQuote())

  return {
    active,
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
    refreshQuote,
    stopAnalysisPolling,
  }
}
