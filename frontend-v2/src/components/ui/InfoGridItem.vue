<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'

withDefaults(defineProps<{
  label: string
  value?: string | number | null
  icon?: string
  colSpan?: 1 | 2 | 3 | 4
}>(), {
  colSpan: 1,
})

const spanClasses: Record<number, string> = {
  1: '',
  2: 'col-span-2',
  3: 'col-span-2 sm:col-span-3',
  4: 'col-span-2 sm:col-span-3 lg:col-span-4',
}
</script>

<template>
  <div :class="spanClasses[colSpan]">
    <dt class="flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500">
      <AppIcon v-if="icon" :name="icon" :size="11" />
      <slot v-else name="icon" />
      <span>{{ label }}</span>
    </dt>
    <dd class="mt-0.5 text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
      <slot>{{ value ?? '—' }}</slot>
    </dd>
  </div>
</template>
