<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonAvatar from '@/components/ui/skeleton/SkeletonAvatar.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <div class="flex items-center gap-3">
        <SkeletonLine width="1/3" height="lg" />
        <Skeleton class="h-6 w-20 rounded-full" />
      </div>
    </div>
    <div class="flex gap-2">
      <Skeleton class="h-9 w-28 rounded-md" />
      <Skeleton class="h-9 w-28 rounded-md" />
    </div>
  </div>

  <!-- Canonical sidebar-left + main-right -->
  <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start">
    <!-- Sidebar (left): Details + SLA + Timeline -->
    <div class="flex flex-col gap-4 lg:w-72 lg:shrink-0">
      <!-- Details card (DetailSidebarCard approximation) -->
      <SkeletonCard>
        <div class="flex flex-col items-center gap-3 text-center">
          <SkeletonAvatar size="xl" />
          <SkeletonLine width="2/3" height="md" />
          <SkeletonLine width="1/2" height="sm" />
          <div class="flex gap-1.5">
            <Skeleton class="h-5 w-14 rounded-full" />
            <Skeleton class="h-5 w-12 rounded-full" />
          </div>
        </div>
        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
          <div class="space-y-3">
            <div v-for="i in 6" :key="i" class="flex justify-between">
              <SkeletonLine width="1/3" height="xs" />
              <SkeletonLine width="1/3" height="xs" />
            </div>
          </div>
        </div>
      </SkeletonCard>

      <!-- SLA card -->
      <SkeletonCard :title="true">
        <div class="space-y-3">
          <SkeletonLine width="2/3" />
          <SkeletonLine width="1/2" />
          <Skeleton class="h-2 w-full rounded-full" />
        </div>
      </SkeletonCard>

      <!-- Activity Timeline card -->
      <SkeletonCard>
        <div class="mb-3 flex items-center gap-2">
          <Skeleton class="h-3.5 w-3.5 rounded" />
          <SkeletonLine width="1/3" height="xs" />
        </div>
        <div class="space-y-4">
          <div v-for="i in 4" :key="i" class="flex items-start gap-3">
            <Skeleton class="mt-0.5 h-3 w-3 shrink-0 rounded-full" />
            <div class="flex-1 space-y-1.5">
              <SkeletonLine :width="i % 2 === 0 ? '3/4' : '1/2'" height="sm" />
              <SkeletonLine width="1/3" height="xs" />
            </div>
          </div>
        </div>
      </SkeletonCard>
    </div>

    <!-- Main (right): Description + Attachments + Comments -->
    <div class="min-w-0 flex-1 space-y-4">
      <!-- Description card -->
      <SkeletonCard>
        <div class="mb-3 flex items-center gap-2">
          <Skeleton class="h-3.5 w-3.5 rounded" />
          <SkeletonLine width="1/5" height="xs" />
        </div>
        <div class="space-y-2.5">
          <SkeletonLine width="full" />
          <SkeletonLine width="3/4" />
          <SkeletonLine width="full" />
          <SkeletonLine width="1/2" />
        </div>
      </SkeletonCard>

      <!-- Attachments card -->
      <SkeletonCard>
        <div class="mb-3 flex items-center gap-2">
          <Skeleton class="h-3.5 w-3.5 rounded" />
          <SkeletonLine width="1/5" height="xs" />
        </div>
        <div class="space-y-2">
          <div
            v-for="i in 2"
            :key="i"
            class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-600"
          >
            <div class="flex items-center gap-2">
              <Skeleton class="h-5 w-5 rounded" />
              <SkeletonLine width="1/2" height="sm" />
            </div>
            <Skeleton class="h-7 w-16 rounded-md" />
          </div>
        </div>
      </SkeletonCard>

      <!-- Comments card -->
      <SkeletonCard :title="true">
        <div class="space-y-4">
          <div v-for="i in 3" :key="i" class="flex items-start gap-3">
            <SkeletonAvatar size="sm" />
            <div class="flex-1 space-y-1.5">
              <div class="flex items-center gap-2">
                <SkeletonLine width="1/4" height="sm" />
                <SkeletonLine width="1/6" height="xs" />
              </div>
              <SkeletonLine :width="i % 2 === 0 ? '3/4' : 'full'" />
            </div>
          </div>
        </div>
      </SkeletonCard>
    </div>
  </div>
</template>
