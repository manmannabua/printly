import { describe, expect, it } from 'vitest'
import { useStorefrontCart, type CartLine } from '@/composables/useStorefrontCart'

function line(overrides: Partial<Omit<CartLine, 'key'>> = {}): Omit<CartLine, 'key'> {
  return {
    productName: 'Document Print',
    summary: '10 pages, B/W, 1 copy',
    totalCents: 500,
    item: { product_id: 'prod-1', quantity: 1 },
    ...overrides,
  }
}

describe('useStorefrontCart', () => {
  it('starts empty with a zero total', () => {
    const { cart, cartTotal } = useStorefrontCart()
    expect(cart.value).toEqual([])
    expect(cartTotal.value).toBe(0)
  })

  it('adds a line and stamps a key derived from the product id', () => {
    const { cart, addLine } = useStorefrontCart()
    addLine(line({ item: { product_id: 'prod-42', quantity: 2 } }))

    expect(cart.value).toHaveLength(1)
    expect(cart.value[0].key.startsWith('prod-42-')).toBe(true)
    expect(cart.value[0].productName).toBe('Document Print')
  })

  it('sums totals across multiple lines', () => {
    const { cartTotal, addLine } = useStorefrontCart()
    addLine(line({ totalCents: 500 }))
    addLine(line({ totalCents: 1250 }))
    addLine(line({ totalCents: 75 }))

    expect(cartTotal.value).toBe(1825)
  })

  it('removes a single line by key without touching the rest', () => {
    const { cart, cartTotal, addLine, removeLine } = useStorefrontCart()
    addLine(line({ item: { product_id: 'a' }, totalCents: 500 }))
    addLine(line({ item: { product_id: 'b' }, totalCents: 300 }))

    const removedKey = cart.value[0].key
    removeLine(removedKey)

    expect(cart.value).toHaveLength(1)
    expect(cart.value[0].item.product_id).toBe('b')
    expect(cartTotal.value).toBe(300)
  })

  it('ignores removal of an unknown key', () => {
    const { cart, addLine, removeLine } = useStorefrontCart()
    addLine(line())
    removeLine('does-not-exist')

    expect(cart.value).toHaveLength(1)
  })

  it('clears the whole cart', () => {
    const { cart, cartTotal, addLine, clear } = useStorefrontCart()
    addLine(line({ totalCents: 500 }))
    addLine(line({ totalCents: 300 }))
    clear()

    expect(cart.value).toEqual([])
    expect(cartTotal.value).toBe(0)
  })
})
