<script setup lang="ts">
import { cn } from '@/lib/utils'
import Skeleton from './Skeleton.vue'

const props = withDefaults(defineProps<{
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
  shape?: 'circle' | 'square'
  class?: string
}>(), {
  size: 'md',
  shape: 'circle',
})

const sizeClass: Record<string, string> = {
  xs:  'h-6 w-6',
  sm:  'h-8 w-8',
  md:  'h-10 w-10',
  lg:  'h-14 w-14',
  xl:  'h-20 w-20',
  '2xl': 'h-28 w-28',
}
</script>

<template>
  <Skeleton
    :class="cn(
      sizeClass[size],
      shape === 'circle' ? 'rounded-full' : 'rounded-lg',
      'shrink-0',
      props.class,
    )"
  />
</template>
