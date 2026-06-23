<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useDarkMode } from '@/composables/useDarkMode'
import AppIcon from '@/components/common/AppIcon.vue'

const { isDark, toggle: toggleDark } = useDarkMode()
const logoError = ref(false)

// Theme palette — primary/hover used for button/link CSS vars on the right panel
const THEME_COLORS: Record<string, { primary: string; hover: string }> = {
  indigo:  { primary: '#4f46e5', hover: '#4338ca' },
  blue:    { primary: '#2563eb', hover: '#1d4ed8' },
  emerald: { primary: '#059669', hover: '#047857' },
  rose:    { primary: '#e11d48', hover: '#be123c' },
  red:     { primary: '#dc2626', hover: '#b91c1c' },
  amber:   { primary: '#d97706', hover: '#b45309' },
  slate:   { primary: '#475569', hover: '#334155' },
}

// CSS vars inherited by child form buttons/links via bg-c-primary / text-c-primary utilities
const cssVars = ref<Record<string, string>>({
  '--c-primary':       THEME_COLORS.indigo.primary,
  '--c-primary-hover': THEME_COLORS.indigo.hover,
})

async function loadBrandingTheme(): Promise<void> {
  try {
    const res  = await fetch('/api/public/v1/branding')
    const json = await res.json() as { data: { color_theme?: string } }
    const name = json.data?.color_theme ?? 'indigo'
    const t    = THEME_COLORS[name] ?? THEME_COLORS.indigo
    cssVars.value = { '--c-primary': t.primary, '--c-primary-hover': t.hover }
  } catch {
    // Keep the indigo default if the fetch fails
  }
}

// Shared format preference — same localStorage key as useSystemClock so the
// toggle state carries over when the user logs in and the navbar clock appears.
const use24Hour = ref<boolean>(true)

const now = ref(new Date())
let clockTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  try { use24Hour.value = localStorage.getItem('clock-format-24h') !== 'false' } catch { /* ignore */ }
  clockTimer = setInterval(() => { now.value = new Date() }, 1000)
  loadBrandingTheme()
})

onUnmounted(() => { if (clockTimer !== null) clearInterval(clockTimer) })

function toggleClockFormat(): void {
  use24Hour.value = !use24Hour.value
  try { localStorage.setItem('clock-format-24h', String(use24Hour.value)) } catch { /* ignore */ }
}

const clockHMS = computed(() => {
  const h24 = now.value.getHours()
  const m   = String(now.value.getMinutes()).padStart(2, '0')
  const s   = String(now.value.getSeconds()).padStart(2, '0')
  if (use24Hour.value) {
    return `${String(h24).padStart(2, '0')}:${m}:${s}`
  }
  const period = h24 >= 12 ? 'PM' : 'AM'
  const h12    = h24 % 12 || 12
  return `${h12}:${m}:${s} ${period}`
})

const clockDay  = computed(() => now.value.toLocaleDateString('en-US', { weekday: 'long' }))
const clockDate = computed(() => now.value.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }))

// Mini calendar — no library, no API
const DAYS_ABBR   = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as const
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as const

const calYear       = computed(() => now.value.getFullYear())
const calMonth      = computed(() => now.value.getMonth())
const todayNum      = computed(() => now.value.getDate())
const calMonthLabel = computed(() => `${MONTH_NAMES[calMonth.value]} ${calYear.value}`)

// Flat array: 0 = empty leading/trailing cell, 1–31 = day number
const calCells = computed((): number[] => {
  const firstDay    = new Date(calYear.value, calMonth.value, 1).getDay()
  const daysInMonth = new Date(calYear.value, calMonth.value + 1, 0).getDate()
  const cells: number[] = []
  for (let i = 0; i < firstDay; i++) cells.push(0)
  for (let d = 1; d <= daysInMonth; d++) cells.push(d)
  while (cells.length % 7 !== 0) cells.push(0)
  return cells
})
</script>

<template>
  <!-- cssVars sets --c-primary/--c-primary-hover inherited by all child form buttons/links -->
  <div class="flex min-h-screen" :style="cssVars">

    <!-- LEFT PANEL — hidden on mobile, visible from lg breakpoint -->
    <!-- Gradient fetched from career site branding settings (database-driven) -->
    <div
      class="relative hidden overflow-hidden bg-gray-900 dark:bg-zinc-950 lg:flex lg:w-1/2 lg:flex-col lg:items-center lg:justify-center xl:w-3/5"
    >

      <!-- Decorative circles — same pattern as the careers hero section -->
      <div class="pointer-events-none absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/5" />
      <div class="pointer-events-none absolute -bottom-16 -right-16 h-56 w-56 rounded-full bg-white/5" />
      <div class="pointer-events-none absolute right-1/4 top-1/3 h-32 w-32 rounded-full bg-white/10" />

      <!-- Digital clock -->
      <div class="relative text-center text-white">
        <!-- Format toggle — same UX as SystemClock in the navbar -->
        <div class="mb-3 flex items-center justify-center gap-2">
          <button
            class="text-white/60 transition-colors hover:text-white"
            :title="use24Hour ? 'Switch to 12-hour format' : 'Switch to 24-hour format'"
            @click="toggleClockFormat"
          >
            <AppIcon name="clock" :size="16" />
          </button>
          <span class="text-xs font-medium uppercase tracking-widest text-white/60">
            {{ use24Hour ? '24H' : '12H' }}
          </span>
        </div>
        <div class="font-mono text-6xl font-light leading-none tracking-tight tabular-nums xl:text-7xl">
          {{ clockHMS }}
        </div>
        <div class="mt-3 text-lg font-medium text-white/80">{{ clockDay }}</div>
        <div class="mt-1 text-sm text-white/60">{{ clockDate }}</div>
      </div>

      <!-- Divider -->
      <div class="relative mx-auto mb-8 mt-10 h-px w-24 bg-white/20" />

      <!-- Mini calendar -->
      <div class="relative w-full max-w-xs rounded-2xl bg-white/10 p-6 backdrop-blur-sm">
        <!-- Month/year header -->
        <div class="mb-3 text-center text-xs font-semibold uppercase tracking-widest text-white/60">
          {{ calMonthLabel }}
        </div>
        <!-- Day-of-week labels -->
        <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-medium text-white/50">
          <span v-for="d in DAYS_ABBR" :key="d">{{ d }}</span>
        </div>
        <!-- Day cells -->
        <div class="grid grid-cols-7 gap-1 text-center text-sm">
          <div
            v-for="(cell, idx) in calCells"
            :key="idx"
            class="mx-auto flex h-8 w-8 items-center justify-center rounded-full"
            :class="{
              'bg-white font-bold text-gray-800 shadow': cell === todayNum,
              'text-white hover:bg-white/10': cell !== 0 && cell !== todayNum,
              'invisible': cell === 0,
            }"
          >
            <span v-if="cell !== 0">{{ cell }}</span>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT PANEL — full-width on mobile, half on desktop -->
    <div class="relative flex w-full flex-col items-center justify-center bg-gray-50 px-6 py-12 dark:bg-black lg:w-1/2 xl:w-2/5">

      <!-- Dark mode toggle -->
      <div class="absolute right-4 top-4">
        <button
          class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-zinc-800"
          :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="toggleDark"
        >
          <AppIcon :name="isDark ? 'sun' : 'moon'" :size="20" />
        </button>
      </div>

      <!-- Logo -->
      <div class="mb-8 text-center">
        <img
          v-if="!logoError"
          src="/logo.png"
          alt="Logo"
          class="mx-auto max-h-16 max-w-[240px] object-contain"
          @error="logoError = true"
        >
        <h1 v-else class="text-3xl font-bold text-primary-600 dark:text-primary-400">HRIS</h1>
      </div>

      <div class="w-full max-w-md">
        <router-view />
      </div>

    </div>

  </div>
</template>
