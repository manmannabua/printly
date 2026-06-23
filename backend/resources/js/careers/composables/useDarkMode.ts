import { ref } from 'vue'

const isDark = ref(false)

function init(): void {
  const stored = localStorage.getItem('hris-dark-mode')
  if (stored !== null) {
    isDark.value = stored === 'true'
  } else {
    isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  document.documentElement.classList.toggle('dark', isDark.value)
}

function toggle(): void {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark', isDark.value)
  localStorage.setItem('hris-dark-mode', String(isDark.value))
}

export function useDarkMode() {
  return { isDark, toggle, init }
}
