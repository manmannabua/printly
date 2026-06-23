<script setup lang="ts">
import { ref, computed } from 'vue'
import { getThemeStyle } from '../theme'
import { useDarkMode } from '../composables/useDarkMode'
import { careersUrl } from '../utils/careersUrl'
import type { NavData, SiteSettingsData } from '../types/careers'

const props = withDefaults(
  defineProps<{
    companyName: string
    navigation?: NavData
    siteSettings?: SiteSettingsData
  }>(),
  {
    navigation: () => ({ header: [], footer: [] }),
    siteSettings: () => ({}),
  },
)

const mobileMenuOpen = ref(false)

const themeStyle = computed(() => getThemeStyle(props.siteSettings?.branding?.color_theme))

const { isDark, toggle: toggleDark } = useDarkMode()

const LOGO_HEIGHT_MAP: Record<string, { header: string; footer: string }> = {
  small: { header: 'h-8', footer: 'h-6' },
  medium: { header: 'h-10', footer: 'h-7' },
  large: { header: 'h-14', footer: 'h-9' },
}

const logoSize = computed(() => {
  const size = props.siteSettings?.branding?.logo_height ?? 'medium'
  return LOGO_HEIGHT_MAP[size] ?? LOGO_HEIGHT_MAP.medium
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-zinc-950" :style="themeStyle">
    <!-- Sticky header -->
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/95">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <!-- Logo -->
        <a :href="careersUrl()" class="flex items-center gap-2 text-xl font-bold text-gray-900 transition-colors hover:text-c-primary dark:text-white">
          <template v-if="siteSettings?.branding?.logo_url">
            <img :src="siteSettings.branding.logo_url" :alt="companyName" :class="[logoSize.header, 'w-auto']" />
          </template>
          <template v-else>
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-c-primary text-sm font-bold text-white">
              {{ companyName.charAt(0) }}
            </span>
          </template>
          <span v-if="!siteSettings?.branding?.logo_url">{{ companyName }} <span class="text-c-primary">Careers</span></span>
        </a>

        <!-- Desktop nav -->
        <nav class="hidden items-center gap-6 sm:flex">
          <a :href="careersUrl()" class="text-sm font-medium text-gray-600 transition-colors hover:text-c-primary dark:text-gray-300 dark:hover:text-c-primary">
            All Positions
          </a>
          <template v-if="navigation?.header">
            <a
              v-for="(item, idx) in navigation.header"
              :key="'header-' + idx"
              :href="item.url"
              :target="item.is_external ? '_blank' : undefined"
              :rel="item.is_external ? 'noopener noreferrer' : undefined"
              class="text-sm font-medium text-gray-600 transition-colors hover:text-c-primary dark:text-gray-300 dark:hover:text-c-primary"
            >
              {{ item.label }}
            </a>
          </template>
          <a :href="careersUrl('contact')" class="text-sm font-medium text-gray-600 transition-colors hover:text-c-primary dark:text-gray-300 dark:hover:text-c-primary">
            Contact Us
          </a>
          <!-- Dark mode toggle -->
          <button
            type="button"
            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-zinc-800"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <svg v-if="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
          </button>
        </nav>

        <!-- Mobile: dark mode + menu button -->
        <div class="flex items-center gap-1 sm:hidden">
          <button
            type="button"
            class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-zinc-800"
            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            @click="toggleDark"
          >
            <svg v-if="isDark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0z" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
          </button>
          <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-zinc-800"
            @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <svg v-if="!mobileMenuOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile menu -->
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="-translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-2 opacity-0"
      >
        <div v-if="mobileMenuOpen" class="border-t border-gray-100 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900 sm:hidden">
          <a
            :href="careersUrl()"
            class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-zinc-800"
            @click="mobileMenuOpen = false"
          >
            All Positions
          </a>
          <template v-if="navigation?.header">
            <a
              v-for="(item, idx) in navigation.header"
              :key="'mobile-' + idx"
              :href="item.url"
              :target="item.is_external ? '_blank' : undefined"
              :rel="item.is_external ? 'noopener noreferrer' : undefined"
              class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-zinc-800"
              @click="mobileMenuOpen = false"
            >
              {{ item.label }}
            </a>
          </template>
          <a
            :href="careersUrl('contact')"
            class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-zinc-800"
            @click="mobileMenuOpen = false"
          >
            Contact Us
          </a>
        </div>
      </Transition>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="border-t border-gray-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
      <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
          <!-- Footer nav links -->
          <nav v-if="navigation?.footer?.length" class="flex flex-wrap items-center gap-4">
            <a
              v-for="(item, idx) in navigation.footer"
              :key="'footer-' + idx"
              :href="item.url"
              :target="item.is_external ? '_blank' : undefined"
              :rel="item.is_external ? 'noopener noreferrer' : undefined"
              class="text-sm text-gray-500 transition-colors hover:text-c-primary dark:text-gray-400"
            >
              {{ item.label }}
            </a>
          </nav>

          <div class="ml-auto text-right">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              &copy; {{ new Date().getFullYear() }} {{ companyName }}. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
