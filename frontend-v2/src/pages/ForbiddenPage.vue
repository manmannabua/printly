<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'
</script>

<template>
  <div class="flex flex-col items-center justify-center py-16">
    <AppIcon name="shield" :size="64" class="mb-6 text-gray-300 dark:text-gray-600" />
    <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100">403</h1>
    <p class="mt-2 text-lg text-gray-500 dark:text-gray-400">Access Denied</p>
    <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
      You don't have permission to access this page.
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
