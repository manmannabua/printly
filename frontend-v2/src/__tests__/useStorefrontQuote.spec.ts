import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useStorefrontQuote } from '@/composables/useStorefrontQuote'
import { quoteStorefront } from '@/services/storefrontService'
import type { OrderFile, Quote, StorefrontProduct } from '@/types/printly'

vi.mock('@/services/storefrontService', () => ({
  quoteStorefront: vi.fn(),
  uploadStorefrontFile: vi.fn(),
  getStorefrontFile: vi.fn(),
}))

const quoteMock = vi.mocked(quoteStorefront)

const fakeQuote: Quote = {
  product_id: 'x',
  pricing_mode: 'file_based',
  total_cents: 1000,
  breakdown: [],
}

function specProduct(): StorefrontProduct {
  return {
    id: 'prod-spec',
    name: 'Tarpaulin',
    pricing_mode: 'spec_based',
    base_price_cents: 5000,
    price_rules: [],
    options: [
      { id: 'opt-size', name: 'Size', choices: [{ label: '2x3' }, { label: '3x4' }] },
      { id: 'opt-finish', name: 'Finish', choices: [{ label: 'Matte' }, { label: 'Glossy' }] },
    ],
  }
}

function fileProduct(): StorefrontProduct {
  return {
    id: 'prod-file',
    name: 'Document Print',
    pricing_mode: 'file_based',
    base_price_cents: 200,
    price_rules: [
      { attribute: 'paper_size', match_value: 'A4', modifier_type: 'per_page', amount_cents: 100, multiplier: null },
      { attribute: 'paper_size', match_value: 'Letter', modifier_type: 'per_page', amount_cents: 120, multiplier: null },
      { attribute: 'paper_size', match_value: 'A4', modifier_type: 'per_page', amount_cents: 100, multiplier: null },
      { attribute: 'duplex', match_value: 'true', modifier_type: 'multiplier', amount_cents: null, multiplier: 0.9 },
    ],
    options: [],
  }
}

function orderFile(overrides: Partial<OrderFile> = {}): OrderFile {
  return {
    id: 'file-1',
    store_id: 'store-1',
    order_item_id: null,
    original_name: 'doc.pdf',
    mime: 'application/pdf',
    size_bytes: 1000,
    page_count: 10,
    paper_size: null,
    is_color: false,
    analysis_status: 'done',
    analysis_error: null,
    upload_token: 'tok-1',
    ...overrides,
  }
}

// Advance past the 350ms quote debounce and let the mocked request resolve.
async function flushQuote(promise: Promise<unknown>): Promise<void> {
  await vi.advanceTimersByTimeAsync(400)
  await promise
}

describe('useStorefrontQuote', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    quoteMock.mockReset()
    quoteMock.mockResolvedValue(fakeQuote)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('seeds spec_based selections with the first choice of each option', () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(specProduct())

    expect(q.isFileBased.value).toBe(false)
    expect(q.config.selections).toEqual({ 'opt-size': '2x3', 'opt-finish': 'Matte' })
    expect(q.config.quantity).toBe(1)
  })

  it('resets prior config when switching products', () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(specProduct())
    q.config.quantity = 9
    q.config.color = 'color'

    q.setProduct(fileProduct())

    expect(q.isFileBased.value).toBe(true)
    expect(q.config.quantity).toBe(1)
    expect(q.config.color).toBe('bw')
    expect(q.config.selections).toEqual({})
  })

  it('dedupes rule paper sizes and surfaces a detected size first', () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(fileProduct())
    expect(q.paperSizes.value).toEqual(['A4', 'Letter'])

    q.currentFile.value = orderFile({ paper_size: 'Legal' })
    expect(q.paperSizes.value).toEqual(['Legal', 'A4', 'Letter'])
  })

  it('does not surface a detected size that a rule already covers', () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(fileProduct())
    q.currentFile.value = orderFile({ paper_size: 'A4' })

    expect(q.paperSizes.value).toEqual(['A4', 'Letter'])
  })

  it('quotes spec_based products by quantity and mapped selections', async () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(specProduct())
    q.config.quantity = 3

    await flushQuote(q.refreshQuote())

    expect(quoteMock).toHaveBeenLastCalledWith('shop', {
      product_id: 'prod-spec',
      quantity: 3,
      selections: [
        { option_id: 'opt-size', choice: '2x3' },
        { option_id: 'opt-finish', choice: 'Matte' },
      ],
    })
    expect(q.quote.value).toEqual(fakeQuote)
  })

  it('quotes file_based products with page count, copies, colour and duplex', async () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(fileProduct())
    q.currentFile.value = orderFile({ page_count: 12 })
    q.config.copies = 2
    q.config.color = 'color'
    q.config.duplex = true

    await flushQuote(q.refreshQuote())

    expect(quoteMock).toHaveBeenLastCalledWith('shop', {
      product_id: 'prod-file',
      page_count: 12,
      color: 'color',
      copies: 2,
      duplex: true,
    })
  })

  it('omits paper_size until one is chosen', async () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(fileProduct())
    q.currentFile.value = orderFile({ page_count: 5 })
    q.config.paper_size = 'A4'

    await flushQuote(q.refreshQuote())

    expect(quoteMock).toHaveBeenLastCalledWith('shop', expect.objectContaining({ paper_size: 'A4' }))
  })

  it('skips the request and clears the quote when a file_based product has no page count', async () => {
    const q = useStorefrontQuote('shop', () => {})
    q.setProduct(fileProduct())
    // No file uploaded yet → no page count → nothing to price.

    await flushQuote(q.refreshQuote())

    expect(quoteMock).not.toHaveBeenCalled()
    expect(q.quote.value).toBeNull()
  })
})
