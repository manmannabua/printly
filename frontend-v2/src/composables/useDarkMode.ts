import { ref, watch, onMounted } from 'vue'

const isDark = ref(false)

function applyTheme(dark: boolean): void {
  if (dark) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

export function useDarkMode() {
  function toggle(): void {
    isDark.value = !isDark.value
  }

  function init(): void {
    const stored = localStorage.getItem('hris-dark-mode')
    if (stored !== null) {
      isDark.value = stored === 'true'
    } else {
      isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
    }
    applyTheme(isDark.value)
  }

  watch(isDark, (value) => {
    localStorage.setItem('hris-dark-mode', String(value))
    applyTheme(value)
  })

  onMounted(() => {
    init()
  })

  return {
    isDark,
    toggle,
    init,
  }
}

export function initDarkMode(): void {
  const stored = localStorage.getItem('hris-dark-mode')
  if (stored !== null) {
    isDark.value = stored === 'true'
  } else {
    isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches
  }
  applyTheme(isDark.value)
}
