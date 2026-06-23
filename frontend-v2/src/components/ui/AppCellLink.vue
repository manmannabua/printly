<script setup lang="ts">
defineProps<{
  mono?: boolean
  medium?: boolean
}>()
</script>

<template>
  <button
    type="button"
    class="text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
    :class="{ 'font-mono text-sm': mono, 'font-medium': medium }"
  >
    <slot />
  </button>
</template>
