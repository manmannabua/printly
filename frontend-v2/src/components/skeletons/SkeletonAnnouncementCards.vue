<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  count?: number
}>(), {
  count: 3,
})
</script>

<template>
  <div class="space-y-4">
    <div
      v-for="n in count"
      :key="n"
      class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="mb-3 flex items-center gap-2">
        <Skeleton class="h-5 w-16 rounded-full" />
        <Skeleton class="h-3 w-28 rounded" />
      </div>
      <Skeleton class="mb-2 h-5 w-3/4 rounded" />
      <Skeleton class="mb-1.5 h-3 w-full rounded" />
      <Skeleton class="mb-1.5 h-3 w-full rounded" />
      <Skeleton class="mb-4 h-3 w-2/3 rounded" />
      <div class="flex items-center gap-3">
        <Skeleton class="h-6 w-6 rounded-full" />
        <Skeleton class="h-3 w-24 rounded" />
        <Skeleton class="h-3 w-16 rounded" />
      </div>
    </div>
  </div>
</template>
