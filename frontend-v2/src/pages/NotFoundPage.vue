<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
    <AppIcon name="exclamation-circle" :size="64" class="mb-6 text-gray-300 dark:text-gray-600" />
    <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100">404</h1>
    <p class="mt-2 text-lg text-gray-500 dark:text-gray-400">Page not found</p>
    <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
      The page you're looking for doesn't exist or has been moved.
    </p>
    <router-link
      to="/dashboard"
      class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600"
    >
      <AppIcon name="arrow-left" :size="16" />
      Go to Dashboard
    </router-link>
  </div>
</template>
