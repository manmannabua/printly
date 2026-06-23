<script setup lang="ts">
defineProps<{
  size?: 'sm' | 'md' | 'lg'
  fullPage?: boolean
  tone?: 'primary' | 'current'
}>()

const sizeClasses: Record<string, string> = {
  sm: 'h-4 w-4 border-2',
  md: 'h-8 w-8 border-2',
  lg: 'h-12 w-12 border-3',
}

const toneClasses: Record<string, string> = {
  primary: 'border-primary-600 dark:border-primary-400',
  current: 'border-current',
}
</script>

<template>
  <div
    v-if="fullPage"
    class="flex h-full min-h-[200px] items-center justify-center"
  >
    <div
      class="animate-spin rounded-full border-t-transparent"
      :class="[sizeClasses[size ?? 'lg'], toneClasses[tone ?? 'primary']]"
    />
  </div>
  <div
    v-else
    class="inline-flex items-center justify-center"
  >
    <div
      class="animate-spin rounded-full border-t-transparent"
      :class="[sizeClasses[size ?? 'md'], toneClasses[tone ?? 'primary']]"
    />
  </div>
</template>
