<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'

// Vary card counts per column for a natural look
const cardCounts = [3, 5, 2, 4]
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <Skeleton class="h-9 w-28 rounded-md" />
  </div>

  <!-- Kanban columns -->
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div v-for="(count, col) in cardCounts" :key="col" class="flex flex-col gap-3">
      <!-- Column header -->
      <div class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800/60">
        <Skeleton class="h-4 w-20 rounded" />
        <Skeleton class="h-5 w-6 rounded-full" />
      </div>
      <!-- Cards -->
      <div
        v-for="card in count"
        :key="card"
        class="space-y-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="flex items-center justify-between">
          <Skeleton class="h-4 w-14 rounded-full" />
          <Skeleton class="h-4 w-10 rounded" />
        </div>
        <Skeleton class="h-3.5 w-full rounded" />
        <Skeleton class="h-3.5 w-3/4 rounded" />
        <div class="flex items-center justify-between pt-1">
          <Skeleton class="h-5 w-16 rounded-full" />
          <Skeleton class="h-5 w-5 rounded-full" />
        </div>
      </div>
    </div>
  </div>
</template>
