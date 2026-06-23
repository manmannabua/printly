<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonTable from '@/components/ui/skeleton/SkeletonTable.vue'

withDefaults(defineProps<{
  rows?: number
  cols?: number
  hasFilters?: boolean
  filterCount?: number
  hasActionButton?: boolean
}>(), {
  rows: 8,
  cols: 6,
  hasFilters: true,
  filterCount: 3,
  hasActionButton: true,
})
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <Skeleton v-if="hasActionButton" class="h-9 w-32 rounded-md" />
  </div>

  <!-- Search + filter bar skeleton -->
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <Skeleton class="h-9 w-64 rounded-md" />
    <template v-if="hasFilters">
      <Skeleton v-for="i in filterCount" :key="i" class="h-9 w-32 rounded-md" />
    </template>
  </div>

  <!-- Table card skeleton (no-padding to match AppCard no-padding) -->
  <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <SkeletonTable :columns="cols" :rows="rows" />
  </div>

  <!-- Pagination skeleton -->
  <div class="mt-4 flex items-center justify-between">
    <Skeleton class="h-3 w-40 rounded" />
    <div class="flex gap-1">
      <Skeleton v-for="i in 5" :key="i" class="h-8 w-8 rounded-md" />
    </div>
  </div>
</template>
