<script setup lang="ts">
const props = withDefaults(defineProps<{
  title: string
  description?: string
  columns?: 2 | 3 | 4
}>(), {
  columns: 2,
})

const gridClasses: Record<number, string> = {
  2: 'grid-cols-1 sm:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
}
</script>

<template>
  <div class="border-b border-gray-200 pb-6 dark:border-gray-700">
    <div class="mb-4">
      <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
        {{ title }}
      </h3>
      <p v-if="description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ description }}
      </p>
    </div>
    <div class="grid gap-4" :class="gridClasses[props.columns]">
      <slot />
    </div>
  </div>
</template>
