import type { Directive, DirectiveBinding } from 'vue'
import { useAuthStore } from '@/stores/auth'

function checkPermission(el: HTMLElement, binding: DirectiveBinding): void {
  const authStore = useAuthStore()
  const modifier = binding.arg
  const value = binding.value

  let hasAccess = false

  if (modifier === 'any') {
    hasAccess = authStore.canAny(value as string[])
  } else if (modifier === 'all') {
    hasAccess = authStore.canAll(value as string[])
  } else {
    hasAccess = authStore.can(value as string)
  }

  if (!hasAccess) {
    if (el.parentNode) {
      el.parentNode.removeChild(el)
    } else {
      el.style.display = 'none'
    }
  }
}

export const canDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    checkPermission(el, binding)
  },
  updated(el: HTMLElement, binding: DirectiveBinding) {
    checkPermission(el, binding)
  },
}
