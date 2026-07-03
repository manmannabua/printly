import { getEcho } from '@/composables/useReverbChannel'

// Optional real-time via Laravel Reverb. The app stays fully functional without
// it (the queue board polls as a fallback), so every failure mode degrades
// quietly rather than breaking the page. The Echo client itself is owned by
// useReverbChannel so the whole app shares a single WS connection.

export interface StoreOrderHandlers {
  onPlaced?: (payload: unknown) => void
  onStatusChanged?: (payload: unknown) => void
  onPrintJobUpdated?: (payload: unknown) => void
}

/**
 * Subscribe to a store's order queue. Returns an unsubscribe function, or null
 * if real-time is unavailable (caller should rely on polling).
 */
export function subscribeToStoreOrders(storeId: string, handlers: StoreOrderHandlers): (() => void) | null {
  const client = getEcho()
  if (!client) return null

  const channelName = `store.${storeId}.orders`
  try {
    const channel = client.private(channelName)
    if (handlers.onPlaced) channel.listen('OrderPlaced', handlers.onPlaced)
    if (handlers.onStatusChanged) channel.listen('OrderStatusChanged', handlers.onStatusChanged)
    if (handlers.onPrintJobUpdated) channel.listen('PrintJobUpdated', handlers.onPrintJobUpdated)

    return () => {
      try {
        client.leave(channelName)
      } catch {
        /* noop */
      }
    }
  } catch {
    return null
  }
}

/**
 * Subscribe to a single order's status changes for the guest status page. Uses a
 * public, code-scoped channel (the order code is the same secret that gates the
 * public REST endpoint), so no broadcast auth handshake is required. Returns an
 * unsubscribe function, or null if real-time is unavailable (caller polls).
 */
export function subscribeToOrder(code: string, onStatusChanged: (payload: unknown) => void): (() => void) | null {
  const client = getEcho()
  if (!client) return null

  const channelName = `orders.${code}`
  try {
    const channel = client.channel(channelName)
    channel.listen('OrderPlaced', onStatusChanged)
    channel.listen('OrderStatusChanged', onStatusChanged)

    return () => {
      try {
        client.leave(channelName)
      } catch {
        /* noop */
      }
    }
  } catch {
    return null
  }
}
