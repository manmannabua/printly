<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
</script>

<template>
  <!-- Page header -->
  <div class="mb-6 flex items-start justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="2/5" height="lg" />
      <SkeletonLine width="1/3" height="sm" />
    </div>
    <div class="flex gap-2">
      <Skeleton class="h-9 w-20 rounded-md" />
      <Skeleton class="h-9 w-20 rounded-md" />
    </div>
  </div>

  <!-- Two-column layout: narrow info + wide table -->
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
    <!-- Left: detail card -->
    <SkeletonCard>
      <div class="space-y-4">
        <SkeletonLine width="1/2" height="xs" />
        <SkeletonDescList :rows="5" :cols="1" />
        <div class="flex gap-2 pt-1">
          <Skeleton class="h-5 w-20 rounded-full" />
          <Skeleton class="h-5 w-16 rounded-full" />
        </div>
      </div>
    </SkeletonCard>

    <!-- Right: table card -->
    <div class="lg:col-span-3">
      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <!-- Table header bar -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
          <SkeletonLine width="1/5" height="sm" />
          <Skeleton class="h-9 w-28 rounded-md" />
        </div>
        <!-- Table column headers -->
        <div class="flex gap-6 border-b border-gray-200 px-6 py-3 dark:border-gray-700">
          <SkeletonLine width="1/4" height="xs" />
          <SkeletonLine width="1/6" height="xs" />
          <SkeletonLine width="1/6" height="xs" />
          <SkeletonLine width="1/6" height="xs" />
          <SkeletonLine width="1/6" height="xs" />
        </div>
        <!-- Table rows -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <div v-for="i in 6" :key="i" class="flex items-center gap-6 px-6 py-4">
            <SkeletonLine width="1/4" height="xs" />
            <SkeletonLine width="1/6" height="xs" />
            <SkeletonLine width="1/6" height="xs" />
            <SkeletonLine width="1/6" height="xs" />
            <SkeletonLine width="1/8" height="xs" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
