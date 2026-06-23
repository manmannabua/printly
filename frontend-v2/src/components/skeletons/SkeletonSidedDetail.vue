<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import SkeletonTable from '@/components/ui/skeleton/SkeletonTable.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  mainCards?: number
  mainRows?: number
  mainCols?: number
  hasTable?: boolean
  tableCols?: number
  tableRows?: number
  sideCards?: number
  sideRows?: number
}>(), {
  mainCards: 1,
  mainRows: 6,
  mainCols: 2,
  hasTable: true,
  tableCols: 5,
  tableRows: 5,
  sideCards: 2,
  sideRows: 4,
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
      <Skeleton class="h-9 w-24 rounded-md" />
      <Skeleton class="h-9 w-28 rounded-md" />
    </div>
  </div>

  <!-- 2-col grid: main (2fr) + sidebar (1fr) -->
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <!-- Main content (left, 2 cols) -->
    <div class="space-y-4 lg:col-span-2">
      <!-- Main detail cards -->
      <SkeletonCard v-for="i in mainCards" :key="i" :title="true">
        <SkeletonDescList :rows="mainRows" :cols="mainCols" />
      </SkeletonCard>

      <!-- Table card -->
      <SkeletonCard v-if="hasTable" :no-padding="true">
        <div class="px-4 py-3">
          <SkeletonLine width="1/4" height="sm" />
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <SkeletonTable :columns="tableCols" :rows="tableRows" />
        </div>
      </SkeletonCard>
    </div>

    <!-- Sidebar (right, 1 col) -->
    <div class="space-y-4">
      <SkeletonCard v-for="i in sideCards" :key="i" :title="true">
        <SkeletonDescList :rows="sideRows" :cols="1" />
      </SkeletonCard>
    </div>
  </div>
</template>
