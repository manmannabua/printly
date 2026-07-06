import { computed, ref } from 'vue'
import type { PlaceOrderItem } from '@/services/storefrontService'

export interface CartLine {
  key: string
  productName: string
  summary: string
  totalCents: number
  item: PlaceOrderItem
}

export function useStorefrontCart() {
  const cart = ref<CartLine[]>([])
  const cartTotal = computed(() => cart.value.reduce((sum, line) => sum + line.totalCents, 0))

  function addLine(line: Omit<CartLine, 'key'>): void {
    cart.value.push({
      ...line,
      key: `${line.item.product_id}-${Date.now()}`,
    })
  }

  function removeLine(key: string): void {
    cart.value = cart.value.filter(line => line.key !== key)
  }

  function clear(): void {
    cart.value = []
  }

  return {
    cart,
    cartTotal,
    addLine,
    removeLine,
    clear,
  }
}
