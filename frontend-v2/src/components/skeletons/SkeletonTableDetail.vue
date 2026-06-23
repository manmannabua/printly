<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonTable from '@/components/ui/skeleton/SkeletonTable.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  summaryRows?: number
  summaryCols?: number
  tableCols?: number
  tableRows?: number
}>(), {
  summaryRows: 2,
  summaryCols: 4,
  tableCols: 6,
  tableRows: 8,
})
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <div class="flex gap-2">
      <Skeleton class="h-9 w-24 rounded-md" />
      <Skeleton class="h-9 w-28 rounded-md" />
    </div>
  </div>

  <!-- Summary card -->
  <SkeletonCard :title="true" class="mb-4">
    <SkeletonDescList :rows="summaryRows" :cols="summaryCols" />
  </SkeletonCard>

  <!-- Data table card -->
  <SkeletonCard :no-padding="true">
    <!-- Table toolbar (search + filters placeholder) -->
    <div class="flex items-center justify-between gap-3 px-4 py-3">
      <Skeleton class="h-9 w-56 rounded-md" />
      <Skeleton class="h-9 w-28 rounded-md" />
    </div>
    <div class="border-t border-gray-200 dark:border-gray-700">
      <SkeletonTable :columns="tableCols" :rows="tableRows" />
    </div>
    <!-- Pagination -->
    <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-gray-700">
      <SkeletonLine width="1/4" height="xs" />
      <div class="flex gap-1">
        <Skeleton v-for="i in 5" :key="i" class="h-8 w-8 rounded-md" />
      </div>
    </div>
  </SkeletonCard>
</template>
