<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'

withDefaults(defineProps<{
  columns?: 1 | 2 | 3 | 4 | 5
  title?: string
  icon?: string
  iconClass?: string
}>(), {
  columns: 4,
  iconClass: 'text-primary-400 dark:text-primary-500',
})

const gridClasses: Record<number, string> = {
  1: 'grid-cols-1',
  2: 'grid-cols-2',
  3: 'grid-cols-2 sm:grid-cols-3',
  4: 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
  5: 'grid-cols-3 sm:grid-cols-5',
}
</script>

<template>
  <section>
    <div v-if="title" class="mb-3 flex items-center gap-2">
      <AppIcon v-if="icon" :name="icon" :size="13" :class="iconClass" />
      <span v-else-if="$slots.icon" class="text-gray-500 dark:text-gray-400">
        <slot name="icon" />
      </span>
      <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
        {{ title }}
      </h3>
      <slot name="title-action" />
    </div>
    <dl class="grid gap-x-6 gap-y-3" :class="gridClasses[columns]">
      <slot />
    </dl>
  </section>
</template>
