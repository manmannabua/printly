<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <Skeleton class="h-8 w-16 rounded-md" />
  </div>

  <!-- Section heading + department filter -->
  <div class="mb-4 flex items-center justify-between">
    <Skeleton class="h-5 w-48 rounded" />
    <div class="flex gap-2">
      <Skeleton class="h-9 w-48 rounded-md" />
      <Skeleton class="h-8 w-24 rounded-md" />
    </div>
  </div>

  <!-- KPI cards row (5 on lg, 3 on sm, 2 on xs) -->
  <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div
      v-for="n in 5"
      :key="n"
      class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <Skeleton class="mb-3 h-3 w-24 rounded" />
      <Skeleton class="mb-2 h-7 w-16 rounded" />
      <Skeleton class="h-3 w-20 rounded" />
    </div>
  </div>

  <!-- Chart grid (2 columns, 2 rows = 4 charts) -->
  <div class="mb-6 grid gap-6 lg:grid-cols-2">
    <div
      v-for="n in 4"
      :key="n"
      class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <Skeleton class="mb-4 h-4 w-36 rounded" />
      <Skeleton class="h-52 w-full rounded-md" />
    </div>
  </div>

  <!-- Personal/employee section (2-col) -->
  <div class="grid gap-6 lg:grid-cols-2">
    <div
      v-for="n in 2"
      :key="n"
      class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <Skeleton class="mb-3 h-4 w-32 rounded" />
      <div class="space-y-2">
        <div v-for="r in 4" :key="r" class="flex items-center justify-between">
          <Skeleton class="h-3 w-28 rounded" />
          <Skeleton class="h-5 w-16 rounded-full" />
        </div>
      </div>
    </div>
  </div>
</template>
