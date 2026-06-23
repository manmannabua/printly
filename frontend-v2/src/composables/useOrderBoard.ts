import { ref, computed } from 'vue'
import type { Order, OrderStatus } from '@/types/printly'

// Order-queue Kanban model — the visual mirror of the backend order state
// machine (OrderService::TRANSITIONS). The backend remains the single source of
// truth; VALID_TRANSITIONS here only drives drag affordance + target resolution.

export interface OrderColumnDef {
  key: string
  label: string
  statuses: OrderStatus[]
}

export interface OrderColumnData extends OrderColumnDef {
  orders: Order[]
}

export const ORDER_COLUMNS: OrderColumnDef[] = [
  { key: 'new', label: 'New', statuses: ['pending_payment'] },
  // Paid orders auto-accept (OrderService), so they rest here rather than in a
  // separate Paid column; the status dot still distinguishes a transient 'paid'.
  // Dragging a New card here resolves to 'paid', which the backend auto-accepts.
  { key: 'accepted', label: 'Accepted', statuses: ['accepted', 'paid'] },
  { key: 'printing', label: 'Printing', statuses: ['in_progress'] },
  { key: 'ready', label: 'Ready', statuses: ['ready'] },
  { key: 'completed', label: 'Completed', statuses: ['completed'] },
  // Multi-status bucket. Order matters: getTargetStatus picks the first status
  // here that's a legal transition from the dragged card, so 'rejected' wins for
  // paid/accepted orders and 'failed' for in-progress/ready ones.
  { key: 'issues', label: 'Issues', statuses: ['rejected', 'cancelled', 'failed', 'refunded'] },
]

// Mirrors OrderService::TRANSITIONS exactly.
export const VALID_TRANSITIONS: Record<string, OrderStatus[]> = {
  draft: ['pending_payment', 'cancelled'],
  pending_payment: ['paid', 'cancelled'],
  paid: ['accepted', 'rejected', 'cancelled'],
  accepted: ['in_progress', 'rejected', 'failed'],
  in_progress: ['ready', 'failed'],
  ready: ['completed', 'failed'],
  rejected: ['refunded'],
  failed: ['refunded'],
  completed: [],
  cancelled: [],
  refunded: [],
}

export const STATUS_LABELS: Record<string, string> = {
  draft: 'Draft',
  pending_payment: 'Pending payment',
  paid: 'Paid',
  accepted: 'Accepted',
  in_progress: 'Printing',
  ready: 'Ready',
  completed: 'Completed',
  cancelled: 'Cancelled',
  rejected: 'Rejected',
  failed: 'Failed',
  refunded: 'Refunded',
}

export const STATUS_DOT: Record<string, string> = {
  draft: 'bg-gray-300',
  pending_payment: 'bg-gray-400',
  paid: 'bg-blue-500',
  accepted: 'bg-indigo-500',
  in_progress: 'bg-amber-500',
  ready: 'bg-emerald-500',
  completed: 'bg-green-600',
  cancelled: 'bg-rose-500',
  rejected: 'bg-rose-500',
  failed: 'bg-rose-500',
  refunded: 'bg-gray-500',
}

export function statusLabel(status: string): string {
  return STATUS_LABELS[status] ?? status.replace(/_/g, ' ')
}

export function useOrderBoard(initial: Order[] = []) {
  const orders = ref<Order[]>(initial)

  const columns = computed<OrderColumnData[]>(() =>
    ORDER_COLUMNS.map(col => ({
      ...col,
      orders: orders.value.filter(o => col.statuses.includes(o.status)),
    })),
  )

  function setOrders(list: Order[]): void {
    orders.value = list
  }

  function isValidDrop(fromStatus: string, toColumnKey: string): boolean {
    const column = ORDER_COLUMNS.find(c => c.key === toColumnKey)
    if (!column) return false
    const targets = VALID_TRANSITIONS[fromStatus] ?? []
    return column.statuses.some(s => targets.includes(s))
  }

  function getTargetStatus(fromStatus: string, toColumnKey: string): OrderStatus | null {
    const column = ORDER_COLUMNS.find(c => c.key === toColumnKey)
    if (!column) return null
    const targets = VALID_TRANSITIONS[fromStatus] ?? []
    return column.statuses.find(s => targets.includes(s)) ?? null
  }

  /** Merge a server patch into the matching order so it re-buckets immediately. */
  function updateOrder(orderId: string, patch: Partial<Order>): void {
    const idx = orders.value.findIndex(o => o.id === orderId)
    if (idx !== -1) orders.value[idx] = { ...orders.value[idx], ...patch } as Order
  }

  return {
    orders,
    columns,
    setOrders,
    isValidDrop,
    getTargetStatus,
    updateOrder,
  }
}
