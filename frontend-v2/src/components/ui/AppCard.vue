<script setup lang="ts">
withDefaults(defineProps<{
  noPadding?: boolean
}>(), {
  noPadding: false,
})
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div
      v-if="$slots.header"
      class="border-b border-gray-200 px-4 py-3 sm:px-6 sm:py-4 dark:border-gray-700"
    >
      <slot name="header" />
    </div>
    <div :class="noPadding ? '' : 'p-4 sm:p-6'">
      <slot />
    </div>
    <div
      v-if="$slots.footer"
      class="border-t border-gray-200 px-4 py-3 sm:px-6 sm:py-4 dark:border-gray-700"
    >
      <slot name="footer" />
    </div>
  </div>
</template>
