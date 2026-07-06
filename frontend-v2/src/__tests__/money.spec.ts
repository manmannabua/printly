import { describe, expect, it } from 'vitest'
import { formatMoney } from '@/utils/money'

describe('formatMoney', () => {
  it('renders zero with two decimals', () => {
    expect(formatMoney(0)).toBe('PHP 0.00')
  })

  it('renders sub-peso amounts from cents', () => {
    expect(formatMoney(1)).toBe('PHP 0.01')
    expect(formatMoney(50)).toBe('PHP 0.50')
    expect(formatMoney(99)).toBe('PHP 0.99')
  })

  it('renders whole pesos', () => {
    expect(formatMoney(100)).toBe('PHP 1.00')
  })

  it('splits cents from pesos correctly', () => {
    expect(formatMoney(12345)).toBe('PHP 123.45')
  })

  it('groups thousands and keeps two decimals', () => {
    // Grouping separator is locale-dependent; only the 2-decimal peso format is pinned.
    expect(formatMoney(150000)).toMatch(/^PHP 1.500\.00$/)
  })

  it('renders negative amounts (e.g. refunds/credits)', () => {
    expect(formatMoney(-100)).toBe('PHP -1.00')
  })
})
