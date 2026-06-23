<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <div class="flex items-start justify-between gap-4 text-sm">
    <span class="shrink-0 text-gray-500 dark:text-gray-400">{{ label }}</span>
    <span class="min-w-0 text-right font-medium text-gray-900 dark:text-gray-100 break-words">
      <slot>—</slot>
    </span>
  </div>
</template>
