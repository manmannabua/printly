<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'
import type { CartLine } from '@/composables/useStorefrontCart'
import { formatMoney } from '@/utils/money'

defineProps<{
  lines: CartLine[]
  totalCents: number
}>()

const emit = defineEmits<{
  keepShopping: []
  checkout: []
  remove: [key: string]
}>()
</script>

<template>
  <div class="min-h-[calc(100dvh-2rem)]">
    <header class="sticky top-0 z-10 -mx-4 -mt-4 border-b border-slate-200 bg-slate-50/95 px-4 pb-3 pt-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <button class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-500" @click="emit('keepShopping')">
        <AppIcon name="arrow-left" :size="17" /> Keep shopping
      </button>
      <div class="flex items-end justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl font-bold text-slate-950 dark:text-white">Your order</h2>
          <p class="mt-1 text-sm text-slate-500">{{ lines.length }} item{{ lines.length === 1 ? '' : 's' }} ready for checkout</p>
        </div>
        <p class="text-xl font-bold text-slate-950 dark:text-white">{{ formatMoney(totalCents) }}</p>
      </div>
    </header>

    <div class="mt-4 space-y-3">
      <div
        v-for="line in lines"
        :key="line.key"
        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
      >
        <div class="flex gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300">
            <AppIcon name="file-text" :size="20" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate font-semibold text-slate-950 dark:text-white">{{ line.productName }}</p>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ line.summary }}</p>
              </div>
              <button class="rounded-full p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40" aria-label="Remove item" @click="emit('remove', line.key)">
                <AppIcon name="trash" :size="18" />
              </button>
            </div>
            <p class="mt-3 text-right text-base font-bold text-slate-950 dark:text-white">{{ formatMoney(line.totalCents) }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[480px] border-t border-slate-200 bg-white/95 px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <button
        class="flex w-full items-center justify-between rounded-2xl bg-blue-600 px-4 py-3 font-semibold text-white shadow-xl shadow-blue-200 dark:shadow-blue-950/40"
        :disabled="!lines.length"
        @click="emit('checkout')"
      >
        <span>Checkout</span>
        <span class="flex items-center gap-2">
          {{ formatMoney(totalCents) }}
          <AppIcon name="arrow-right" :size="18" />
        </span>
      </button>
    </div>
  </div>
</template>
