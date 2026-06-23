<script setup lang="ts">
import { cn } from '@/lib/utils'

withDefaults(defineProps<{
  variant?: 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'primary'
  size?: 'sm' | 'md'
  dot?: boolean
}>(), {
  variant: 'neutral',
  size: 'md',
})

const variantClasses: Record<string, string> = {
  success: 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
  warning: 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300',
  danger: 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300',
  info: 'bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300',
  neutral: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  primary: 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
}

const dotClasses: Record<string, string> = {
  success: 'bg-success-500',
  warning: 'bg-warning-500',
  danger: 'bg-danger-500',
  info: 'bg-info-500',
  neutral: 'bg-gray-500',
  primary: 'bg-primary-500',
}

const sizeClasses: Record<string, string> = {
  sm: 'px-1.5 py-0.5 text-xs',
  md: 'px-2.5 py-0.5 text-xs',
}
</script>

<template>
  <span
    :class="cn(
      'inline-flex items-center gap-1 rounded-full font-medium',
      variantClasses[variant],
      sizeClasses[size],
    )"
  >
    <span
      v-if="dot"
      class="h-1.5 w-1.5 rounded-full"
      :class="dotClasses[variant]"
    />
    <slot />
  </span>
</template>
