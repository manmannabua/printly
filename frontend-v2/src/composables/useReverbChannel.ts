import { ref, onUnmounted } from 'vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from '@/services/api'

// Single Laravel Reverb (Echo) client for the whole app. Real-time is optional:
// if no app key is configured the client is null and every subscribe call becomes
// a no-op, so callers degrade quietly to polling instead of breaking the page.

type ReverbEcho = Echo<'reverb'>

// Mirror of pusher-js' ChannelAuthorizationData (what /broadcasting/auth returns).
interface ChannelAuthData {
  auth: string
  channel_data?: string
  shared_secret?: string
}

let echoInstance: ReverbEcho | null = null
let triedInit = false

/**
 * Lazily build (once) and return the shared Echo client, or null when Reverb is
 * not configured. Private channels authorize through our axios instance so the
 * session cookie + XSRF token are sent (default Echo XHR omits credentials).
 */
export function getEcho(): ReverbEcho | null {
  if (triedInit) return echoInstance
  triedInit = true

  const key = import.meta.env.VITE_REVERB_APP_KEY as string | undefined
  if (!key || key === 'your-reverb-app-key') return null

  const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https'

  try {
    ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

    echoInstance = new Echo<'reverb'>({
      broadcaster: 'reverb',
      key,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
      forceTLS,
      enabledTransports: forceTLS ? ['ws', 'wss'] : ['ws'],
      authorizer: (channel: { name: string }) => ({
        authorize: (socketId: string, callback: (error: Error | null, data: ChannelAuthData | null) => void) => {
          api.post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
            .then(({ data }) => callback(null, data))
            .catch(err => callback(err, null))
        },
      }),
    })
  } catch {
    echoInstance = null
  }

  return echoInstance
}

// Module-level channel reference counting. Prevents echo.leave() from tearing
// down a channel that other components/composables are still listening on.
const channelRefCount = new Map<string, number>()

export interface ChannelSubscription {
  channel: string
  type: 'public' | 'private'
  unsubscribe: () => void
}

const NOOP_SUBSCRIPTION: ChannelSubscription = {
  channel: '',
  type: 'public',
  unsubscribe: () => { /* noop */ },
}

export interface UseReverbChannelReturn {
  subscribeToPrivate: <T>(channel: string, event: string, callback: (data: T) => void) => ChannelSubscription
  subscribeToPublic: <T>(channel: string, event: string, callback: (data: T) => void) => ChannelSubscription
  unsubscribeAll: () => void
}

export function useReverbChannel(): UseReverbChannelReturn {
  const subscriptions = ref<ChannelSubscription[]>([])

  function subscribe<T>(
    kind: 'private' | 'public',
    channel: string,
    event: string,
    callback: (data: T) => void,
  ): ChannelSubscription {
    const echo = getEcho()
    if (!echo) return NOOP_SUBSCRIPTION

    // echo.leave() expects the prefixed name for private channels.
    const channelKey = kind === 'private' ? `private-${channel}` : channel
    channelRefCount.set(channelKey, (channelRefCount.get(channelKey) ?? 0) + 1)

    const ch = kind === 'private' ? echo.private(channel) : echo.channel(channel)

    let active = true
    ch.listen(event, (data: T) => {
      if (active) callback(data)
    })

    const subscription: ChannelSubscription = {
      channel,
      type: kind,
      unsubscribe: () => {
        if (!active) return
        active = false
        const count = (channelRefCount.get(channelKey) ?? 1) - 1
        if (count <= 0) {
          echo.leave(channelKey)
          channelRefCount.delete(channelKey)
        } else {
          channelRefCount.set(channelKey, count)
        }
        subscriptions.value = subscriptions.value.filter(s => s !== subscription)
      },
    }

    subscriptions.value.push(subscription)
    return subscription
  }

  function subscribeToPrivate<T>(channel: string, event: string, callback: (data: T) => void): ChannelSubscription {
    return subscribe('private', channel, event, callback)
  }

  function subscribeToPublic<T>(channel: string, event: string, callback: (data: T) => void): ChannelSubscription {
    return subscribe('public', channel, event, callback)
  }

  function unsubscribeAll(): void {
    for (const sub of [...subscriptions.value]) sub.unsubscribe()
    subscriptions.value = []
  }

  onUnmounted(unsubscribeAll)

  return { subscribeToPrivate, subscribeToPublic, unsubscribeAll }
}
