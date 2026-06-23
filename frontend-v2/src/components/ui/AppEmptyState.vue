<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'

withDefaults(defineProps<{
  icon?: string
  title?: string
  description?: string
  /** Compact variant for use inside table rows / cards — smaller icon, less padding. */
  compact?: boolean
}>(), {
  icon: 'list',
  title: 'No data found',
  compact: false,
})
</script>

<template>
  <div
    class="flex flex-col items-center justify-center text-center"
    :class="compact ? 'py-6' : 'py-12'"
  >
    <div
      class="rounded-full bg-gray-100 dark:bg-gray-700"
      :class="compact ? 'mb-2 p-2' : 'mb-4 p-4'"
    >
      <AppIcon
        :name="icon"
        :size="compact ? 18 : 32"
        class="text-gray-400 dark:text-gray-500"
      />
    </div>
    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100" :class="compact ? '' : 'mb-1'">
      {{ title }}
    </h3>
    <p
      v-if="description"
      class="max-w-sm text-gray-500 dark:text-gray-400"
      :class="compact ? 'mt-0.5 text-xs' : 'mb-4 text-sm'"
    >
      {{ description }}
    </p>
    <div v-if="$slots.action" :class="compact ? 'mt-3' : ''">
      <slot name="action" />
    </div>
  </div>
</template>
