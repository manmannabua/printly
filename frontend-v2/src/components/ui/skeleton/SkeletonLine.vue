<script setup lang="ts">
import { cn } from '@/lib/utils'
import Skeleton from './Skeleton.vue'

const props = withDefaults(defineProps<{
  width?: 'full' | '3/4' | '2/3' | '1/2' | '1/3' | '1/4'
  height?: 'xs' | 'sm' | 'md' | 'lg'
  class?: string
}>(), {
  width: 'full',
  height: 'sm',
})

const widthClass: Record<string, string> = {
  full: 'w-full',
  '3/4': 'w-3/4',
  '2/3': 'w-2/3',
  '1/2': 'w-1/2',
  '1/3': 'w-1/3',
  '1/4': 'w-1/4',
}

const heightClass: Record<string, string> = {
  xs: 'h-2.5',
  sm: 'h-3.5',
  md: 'h-4',
  lg: 'h-5',
}
</script>

<template>
  <Skeleton :class="cn(widthClass[width], heightClass[height], props.class)" />
</template>
