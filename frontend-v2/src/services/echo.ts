import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from '@/services/api'

// Optional real-time via Laravel Reverb. The app stays fully functional without
// it (the queue board polls as a fallback), so every failure mode degrades
// quietly rather than breaking the page.

type AnyEcho = Echo<'reverb'>

let echo: AnyEcho | null = null
let triedInit = false

function init(): AnyEcho | null {
  if (triedInit) return echo
  triedInit = true

  const key = import.meta.env.VITE_REVERB_APP_KEY as string | undefined
  if (!key || key === 'your-reverb-app-key') return null

  try {
    ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

    echo = new Echo<'reverb'>({
      broadcaster: 'reverb',
      key,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
      // Authorize private channels through our axios instance so the session
      // cookie + XSRF token are sent (default Echo XHR omits credentials).
      authorizer: (channel: { name: string }) => ({
        authorize: (socketId: string, callback: (error: boolean, data: unknown) => void) => {
          api.post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
            .then(({ data }) => callback(false, data))
            .catch(err => callback(true, err))
        },
      }),
    })
  } catch {
    echo = null
  }

  return echo
}

export interface StoreOrderHandlers {
  onPlaced?: (payload: unknown) => void
  onStatusChanged?: (payload: unknown) => void
}

/**
 * Subscribe to a store's order queue. Returns an unsubscribe function, or null
 * if real-time is unavailable (caller should rely on polling).
 */
export function subscribeToStoreOrders(storeId: string, handlers: StoreOrderHandlers): (() => void) | null {
  const client = init()
  if (!client) return null

  const channelName = `store.${storeId}.orders`
  try {
    const channel = client.private(channelName)
    if (handlers.onPlaced) channel.listen('OrderPlaced', handlers.onPlaced)
    if (handlers.onStatusChanged) channel.listen('OrderStatusChanged', handlers.onStatusChanged)

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
