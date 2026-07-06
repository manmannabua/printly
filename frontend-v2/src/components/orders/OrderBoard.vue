<script setup lang="ts">
import { ref } from 'vue'
import type { Order, OrderStatus } from '@/types/printly'
import type { OrderColumnData } from '@/composables/useOrderBoard'
import OrderColumn from './OrderColumn.vue'
import OrderTransitionModal from './OrderTransitionModal.vue'
import { useDragScroll } from '@/composables/useDragScroll'

const props = withDefaults(defineProps<{
  columns: OrderColumnData[]
  isValidDrop: (fromStatus: string, toColumnKey: string) => boolean
  getTargetStatus: (fromStatus: string, toColumnKey: string) => OrderStatus | null
  canProcess?: boolean
  fullscreen?: boolean
}>(), { canProcess: false, fullscreen: false })

const emit = defineEmits<{
  (e: 'transition', order: Order, newStatus: OrderStatus, reason?: string): void
  (e: 'chat', order: Order): void
  (e: 'open', order: Order): void
}>()

const dragOrder = ref<Order | null>(null)
const showModal = ref(false)
const pending = ref<{ order: Order; newStatus: OrderStatus } | null>(null)
const scrollRef = ref<HTMLElement | null>(null)
useDragScroll(scrollRef)

function onDragStart(order: Order) {
  if (!props.canProcess) return
  dragOrder.value = order
}

function onDrop(columnKey: string) {
  if (!dragOrder.value) return

  const newStatus = props.getTargetStatus(dragOrder.value.status, columnKey)
  if (!newStatus) {
    dragOrder.value = null
    return
  }

  pending.value = { order: dragOrder.value, newStatus }
  showModal.value = true
  dragOrder.value = null
}

function onDragEnd() {
  dragOrder.value = null
}

function confirmTransition(reason?: string) {
  if (!pending.value) return
  emit('transition', pending.value.order, pending.value.newStatus, reason)
  showModal.value = false
  pending.value = null
}

function cancelTransition() {
  showModal.value = false
  pending.value = null
}
</script>

<template>
  <div
    ref="scrollRef"
    class="grid grid-flow-col auto-cols-[minmax(220px,1fr)] items-stretch gap-3 overflow-x-auto pb-3 xl:grid-flow-row xl:grid-cols-6 xl:overflow-x-visible"
    :class="props.fullscreen ? 'h-full min-h-0 flex-1' : 'h-[calc(100vh-252px)]'"
    @dragend="onDragEnd"
  >
    <OrderColumn
      v-for="column in columns"
      :key="column.key"
      :column-key="column.key"
      :label="column.label"
      :orders="column.orders"
      :can-process="props.canProcess"
      :is-valid-drop="dragOrder ? isValidDrop(dragOrder.status, column.key) : false"
      :is-drag-active="!!dragOrder"
      @drag-start="onDragStart"
      @drop="onDrop(column.key)"
      @chat="emit('chat', $event)"
      @open="emit('open', $event)"
    />
  </div>

  <OrderTransitionModal
    v-if="pending"
    :model-value="showModal"
    :order-code="pending.order.code"
    :from-status="pending.order.status"
    :to-status="pending.newStatus"
    @confirm="confirmTransition"
    @cancel="cancelTransition"
  />
</template>
