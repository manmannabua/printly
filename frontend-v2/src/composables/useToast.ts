import type { ComponentPublicInstance } from 'vue'
import { ref } from 'vue'
import type { Toast, ToastOptions } from '@/components/common/AppToast.vue'

type ToastType = Toast['type']

interface ToastInstance {
  add: (type: ToastType, message: string, duration?: number, options?: ToastOptions) => void
  remove: (id: number) => void
}

const toastRef = ref<ToastInstance | null>(null)

export function setToastRef(instance: ComponentPublicInstance | null): void {
  toastRef.value = instance as unknown as ToastInstance | null
}

export function useToast() {
  function success(message: string, duration?: number, options?: ToastOptions): void {
    toastRef.value?.add('success', message, duration, options)
  }

  function error(message: string, duration?: number, options?: ToastOptions): void {
    toastRef.value?.add('error', message, duration, options)
  }

  function warning(message: string, duration?: number, options?: ToastOptions): void {
    toastRef.value?.add('warning', message, duration, options)
  }

  function info(message: string, duration?: number, options?: ToastOptions): void {
    toastRef.value?.add('info', message, duration, options)
  }

  return { success, error, warning, info }
}
