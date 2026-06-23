<script setup lang="ts">
import { ref } from 'vue'
import { useDarkMode } from '@/composables/useDarkMode'
import AppIcon from '@/components/common/AppIcon.vue'

const { isDark, toggle: toggleDark } = useDarkMode()
const logoError = ref(false)

// Deep cyan, drawn from the CMYK palette — the brand primary inherited by every
// child form button/link via the bg-c-primary / text-c-primary utilities.
const cssVars = {
  '--c-primary': '#0891b2',
  '--c-primary-hover': '#0e7490',
}
</script>

<template>
  <div class="flex min-h-screen font-body" :style="cssVars">

    <!-- LEFT — the press. Ink panel, hidden below lg. -->
    <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-[#15110d] p-12 text-white lg:flex xl:w-3/5">
      <!-- Halftone dot field: ink fading across the sheet -->
      <div
        class="pointer-events-none absolute inset-0 opacity-[0.07]"
        style="background-image: radial-gradient(#fff 1.4px, transparent 1.4px); background-size: 14px 14px;"
      />
      <div class="pointer-events-none absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-[#15110d] to-transparent" />

      <!-- Wordmark + CMYK chip -->
      <div class="relative flex items-center gap-3">
        <span class="flex gap-0.5" aria-hidden="true">
          <span class="h-4 w-1.5 bg-[#00aeef]" />
          <span class="h-4 w-1.5 bg-[#ec008c]" />
          <span class="h-4 w-1.5 bg-[#ffd400]" />
          <span class="h-4 w-1.5 bg-white" />
        </span>
        <span class="font-display text-2xl font-bold tracking-tight">Printly</span>
      </div>

      <!-- Proof: the characteristic object — a job framed in registration marks -->
      <div class="relative mx-auto w-full max-w-sm">
        <!-- crop marks -->
        <span class="absolute -left-3 -top-3 h-6 w-6 border-l border-t border-white/40" />
        <span class="absolute -right-3 -top-3 h-6 w-6 border-r border-t border-white/40" />
        <span class="absolute -bottom-3 -left-3 h-6 w-6 border-b border-l border-white/40" />
        <span class="absolute -bottom-3 -right-3 h-6 w-6 border-b border-r border-white/40" />

        <div class="rounded-sm bg-white/[0.04] p-7 ring-1 ring-white/10 backdrop-blur-sm">
          <p class="font-mono text-[11px] uppercase tracking-[0.3em] text-white/40">Job ticket</p>
          <p class="mt-4 font-display text-4xl font-semibold leading-[1.1]">
            Order. Queue.<br>Out the door.
          </p>
          <p class="mt-4 text-sm leading-relaxed text-white/55">
            The print-shop counter, online — orders, payments, and the live job queue in one place.
          </p>
          <!-- registration crosshair -->
          <div class="mt-7 flex items-center gap-3 text-white/30">
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
              <circle cx="11" cy="11" r="7" stroke="currentColor" />
              <path d="M11 0v22M0 11h22" stroke="currentColor" />
            </svg>
            <span class="font-mono text-[11px] tracking-widest">REGISTERED · CMYK</span>
          </div>
        </div>
      </div>

      <p class="relative font-mono text-[11px] tracking-widest text-white/30">
        © {{ new Date().getFullYear() }} PRINTLY — STATION ACCESS
      </p>
    </div>

    <!-- RIGHT — the proof sheet. Form on paper. -->
    <div class="relative flex w-full flex-col items-center justify-center bg-[#faf9f6] px-6 py-12 dark:bg-zinc-950 lg:w-1/2 xl:w-2/5">

      <button
        class="absolute right-4 top-4 rounded-lg p-2 text-gray-400 transition-colors hover:bg-black/5 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleDark"
      >
        <AppIcon :name="isDark ? 'sun' : 'moon'" :size="20" />
      </button>

      <!-- Mobile wordmark (left panel hidden) -->
      <div class="mb-8 flex items-center gap-2 lg:hidden">
        <span class="flex gap-0.5" aria-hidden="true">
          <span class="h-3.5 w-1 bg-[#00aeef]" />
          <span class="h-3.5 w-1 bg-[#ec008c]" />
          <span class="h-3.5 w-1 bg-[#ffd400]" />
          <span class="h-3.5 w-1 bg-zinc-900 dark:bg-white" />
        </span>
        <img
          v-if="!logoError"
          src="/logo.png"
          alt="Printly"
          class="max-h-8 max-w-[160px] object-contain"
          @error="logoError = true"
        >
        <span v-else class="font-display text-2xl font-bold text-gray-900 dark:text-white">Printly</span>
      </div>

      <!-- Form framed as a proof, with crop marks + CMYK registration bar -->
      <div class="relative w-full max-w-md">
        <span class="absolute -left-3 -top-3 h-5 w-5 border-l-2 border-t-2 border-gray-300 dark:border-zinc-700" />
        <span class="absolute -right-3 -top-3 h-5 w-5 border-r-2 border-t-2 border-gray-300 dark:border-zinc-700" />
        <span class="absolute -bottom-3 -left-3 h-5 w-5 border-b-2 border-l-2 border-gray-300 dark:border-zinc-700" />
        <span class="absolute -bottom-3 -right-3 h-5 w-5 border-b-2 border-r-2 border-gray-300 dark:border-zinc-700" />

        <div class="rounded-sm border border-gray-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <!-- CMYK registration hairline -->
          <div class="mb-7 flex h-1 gap-px overflow-hidden rounded-full" aria-hidden="true">
            <span class="flex-1 bg-[#00aeef]" />
            <span class="flex-1 bg-[#ec008c]" />
            <span class="flex-1 bg-[#ffd400]" />
            <span class="flex-1 bg-zinc-900 dark:bg-zinc-200" />
          </div>
          <router-view />
        </div>
      </div>

    </div>
  </div>
</template>
