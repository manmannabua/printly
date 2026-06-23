<script setup lang="ts">
import { ref } from 'vue'
import { useDarkMode } from '@/composables/useDarkMode'
import AppIcon from '@/components/common/AppIcon.vue'

const { isDark, toggle: toggleDark } = useDarkMode()
const logoError = ref(false)
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-black">
    <!-- Dark mode toggle -->
    <div class="absolute right-4 top-4">
      <button
        class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-zinc-800"
        @click="toggleDark"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
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
        class="mx-auto mb-2 max-h-16 max-w-[300px] object-contain"
        @error="logoError = true"
      >
      <h1 v-else class="text-3xl font-bold text-primary-600 dark:text-primary-400">HRIS</h1>
    </div>

    <!-- Content card -->
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <router-view />
    </div>
  </div>
</template>
