import { registerSW } from 'virtual:pwa-register'

export const updateServiceWorker = registerSW({
  immediate: true,
  onNeedRefresh() {
    window.dispatchEvent(new CustomEvent('printly:pwa-update'))
  },
  onOfflineReady() {
    window.dispatchEvent(new CustomEvent('printly:pwa-offline-ready'))
  },
  onRegisteredSW(_swUrl, registration) {
    if (!registration) return

    setInterval(() => {
      void registration.update()
    }, 60 * 60 * 1000)
  },
})
