<script setup lang="ts">
import { cn } from '@/lib/utils'
import SkeletonLine from './SkeletonLine.vue'

const props = withDefaults(defineProps<{
  title?: boolean
  class?: string
  noPadding?: boolean
}>(), {
  title: false,
  noPadding: false,
})
</script>

<template>
  <div
    :class="cn(
      'overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800',
      props.class,
    )"
  >
    <!-- Optional card title skeleton in header -->
    <div v-if="title" class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
      <SkeletonLine width="1/3" height="md" />
    </div>
    <!-- Content slot -->
    <div :class="noPadding ? '' : 'p-6'">
      <slot />
    </div>
  </div>
</template>
