<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonTable from '@/components/ui/skeleton/SkeletonTable.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <div class="flex flex-wrap gap-2">
      <Skeleton class="h-9 w-24 rounded-md" />
      <Skeleton class="h-9 w-24 rounded-md" />
      <Skeleton class="h-9 w-20 rounded-md" />
    </div>
  </div>

  <!-- Status badges row -->
  <div class="mb-6 flex flex-wrap gap-3">
    <Skeleton class="h-6 w-24 rounded-full" />
    <Skeleton class="h-6 w-28 rounded-full" />
  </div>

  <!-- Details grid: 3 cards -->
  <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <!-- Invoice Details -->
    <SkeletonCard :title="true">
      <div class="space-y-3">
        <div v-for="i in 5" :key="i" class="flex justify-between gap-4">
          <SkeletonLine width="1/3" height="xs" />
          <SkeletonLine :width="i % 2 === 0 ? '1/2' : '1/3'" height="sm" />
        </div>
      </div>
    </SkeletonCard>

    <!-- Amounts card -->
    <SkeletonCard :title="true">
      <div class="space-y-3">
        <div v-for="i in 4" :key="i" class="flex justify-between gap-4">
          <SkeletonLine width="1/3" height="xs" />
          <SkeletonLine :width="i === 4 ? '1/2' : '1/3'" height="sm" />
        </div>
        <div class="flex justify-between gap-4 border-t pt-2 dark:border-gray-600">
          <SkeletonLine width="1/3" height="xs" />
          <SkeletonLine width="1/2" height="md" />
        </div>
      </div>
    </SkeletonCard>

    <!-- PO card -->
    <SkeletonCard :title="true">
      <div class="space-y-3">
        <div v-for="i in 3" :key="i" class="flex justify-between gap-4">
          <SkeletonLine width="1/3" height="xs" />
          <SkeletonLine :width="i % 2 === 0 ? '1/2' : '1/3'" height="sm" />
        </div>
      </div>
    </SkeletonCard>
  </div>

  <!-- Items table card -->
  <SkeletonCard :title="true" class="mb-6" :no-padding="true">
    <div class="px-0">
      <SkeletonTable :columns="6" :rows="4" />
    </div>
  </SkeletonCard>

  <!-- Status history card -->
  <SkeletonCard :title="true">
    <div class="space-y-4">
      <div v-for="i in 3" :key="i" class="flex items-start justify-between">
        <div class="flex items-center gap-3">
          <Skeleton class="h-3 w-3 shrink-0 rounded-full" />
          <div class="space-y-1.5">
            <SkeletonLine :width="i % 2 === 0 ? '1/2' : '3/4'" height="sm" />
            <SkeletonLine width="1/3" height="xs" />
          </div>
        </div>
        <SkeletonLine width="1/4" height="xs" />
      </div>
    </div>
  </SkeletonCard>
</template>
