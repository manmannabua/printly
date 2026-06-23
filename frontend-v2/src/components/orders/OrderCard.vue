<script setup lang="ts">
import type { Order } from '@/types/printly'
import { STATUS_DOT } from '@/composables/useOrderBoard'
import AppIcon from '@/components/common/AppIcon.vue'

const props = defineProps<{
  order: Order
  draggable?: boolean
}>()

defineEmits<{
  (e: 'drag-start', order: Order): void
  (e: 'chat', order: Order): void
  (e: 'open', order: Order): void
}>()

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function relativeTime(iso: string | null): string {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const mins = Math.round(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.round(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.round(hrs / 24)}d ago`
}

function itemsSummary(o: Order): string {
  return (o.items ?? []).map(i => `${i.product_name} ×${i.quantity}`).join(', ') || '—'
}
</script>

<template>
  <div
    class="cursor-grab rounded-md border border-gray-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing dark:border-gray-700 dark:bg-gray-800"
    :class="{ 'cursor-default active:cursor-default': !draggable }"
    :draggable="draggable"
    @dragstart="$emit('drag-start', order)"
  >
    <div class="flex items-start gap-2.5">
      <span
        class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
        :class="STATUS_DOT[order.status] ?? 'bg-gray-400'"
      />
      <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2">
          <button
            class="rounded font-mono text-sm font-semibold text-gray-900 hover:text-primary-600 hover:underline dark:text-gray-100 dark:hover:text-primary-400"
            title="View order details"
            @click.stop="$emit('open', order)"
          >{{ order.code }}</button>
          <button
            class="inline-flex shrink-0 items-center justify-center rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-700"
            title="Open chat with customer"
            @click.stop="$emit('chat', order)"
          >
            <AppIcon name="message-circle" class="h-4 w-4" />
          </button>
        </div>
        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ itemsSummary(order) }}</p>
        <div class="mt-1.5 flex items-center justify-between gap-2">
          <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ formatMoney(order.total_cents) }}</span>
          <span class="truncate text-xs capitalize text-gray-400">{{ order.payment_status.replace(/_/g, ' ') }}</span>
        </div>
        <div v-if="order.placed_at" class="mt-0.5 flex items-center gap-1 text-xs text-gray-400">
          <AppIcon name="clock" class="h-3 w-3 shrink-0" />
          <span>{{ relativeTime(order.placed_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
