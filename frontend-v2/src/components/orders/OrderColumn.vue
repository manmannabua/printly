<script setup lang="ts">
import type { Order } from '@/types/printly'
import OrderCard from './OrderCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const props = defineProps<{
  columnKey: string
  label: string
  orders: Order[]
  isValidDrop: boolean
  isDragActive: boolean
  canProcess: boolean
}>()

const emit = defineEmits<{
  (e: 'drag-start', order: Order): void
  (e: 'drop'): void
  (e: 'chat', order: Order): void
  (e: 'open', order: Order): void
}>()

function onDragOver(event: DragEvent) {
  if (props.isValidDrop) {
    event.preventDefault()
  }
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  emit('drop')
}
</script>

<template>
  <div
    class="flex min-w-0 flex-col rounded-lg border p-2.5 transition-colors"
    :class="{
      'border-primary-400 bg-primary-50 dark:border-primary-500 dark:bg-primary-900/20': isDragActive && isValidDrop,
      'border-gray-200 bg-gray-50 opacity-50 dark:border-gray-700 dark:bg-gray-900': isDragActive && !isValidDrop,
      'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900': !isDragActive,
    }"
    @dragover="onDragOver"
    @drop="onDrop"
  >
    <div class="mb-2 flex items-center justify-between gap-2">
      <h3 class="truncate text-sm font-semibold text-gray-700 dark:text-gray-300">{{ label }}</h3>
      <AppBadge variant="neutral" size="sm">{{ orders.length }}</AppBadge>
    </div>
    <div class="card-scroll flex flex-1 flex-col gap-2 overflow-y-auto">
      <OrderCard
        v-for="order in orders"
        :key="order.id"
        :order="order"
        :draggable="canProcess"
        @drag-start="emit('drag-start', $event)"
        @chat="emit('chat', $event)"
        @open="emit('open', $event)"
      />
      <div v-if="orders.length === 0" class="flex flex-1 items-center justify-center py-8 text-xs text-gray-400">
        No orders
      </div>
    </div>
  </div>
</template>

<style scoped>
.card-scroll {
  scrollbar-width: thin;
  scrollbar-color: transparent transparent;
}
.card-scroll:hover {
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}
.card-scroll::-webkit-scrollbar {
  width: 4px;
}
.card-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.card-scroll::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 4px;
}
.card-scroll:hover::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
}
</style>
