<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
</script>

<template>
  <!-- Header: breadcrumb + title/subtitle + "Back to detail" action -->
  <div class="mb-6 flex items-start justify-between gap-4">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
      <SkeletonLine width="2/3" height="sm" />
    </div>
    <Skeleton class="h-9 w-28 rounded-md" />
  </div>

  <!-- 4/8 two-column grid matching the real page -->
  <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
    <!-- Left: capture card -->
    <div class="lg:col-span-4">
      <SkeletonCard :title="true">
        <div class="space-y-3">
          <!-- Full-name input -->
          <div class="space-y-1">
            <SkeletonLine width="1/3" height="xs" />
            <Skeleton class="h-9 w-full rounded-md" />
          </div>

          <!-- Method toggle (Draw / Upload PNG) -->
          <div class="flex gap-1">
            <Skeleton class="h-8 w-20 rounded-md" />
            <Skeleton class="h-8 w-24 rounded-md" />
          </div>

          <!-- Signature pad -->
          <Skeleton class="h-40 w-full rounded-md" />

          <!-- Consent (checkbox + 2 lines of label) -->
          <div class="flex gap-2 pt-1">
            <Skeleton class="h-4 w-4 shrink-0 rounded" />
            <div class="flex-1 space-y-1.5">
              <SkeletonLine width="full" height="sm" />
              <SkeletonLine width="3/4" height="xs" />
            </div>
          </div>

          <!-- Primary + secondary action buttons -->
          <div class="flex flex-col gap-2 pt-2">
            <Skeleton class="h-10 w-full rounded-md" />
            <Skeleton class="h-9 w-full rounded-md" />
          </div>
        </div>
      </SkeletonCard>
    </div>

    <!-- Right: PDF viewer card -->
    <div class="lg:col-span-8">
      <SkeletonCard :title="true">
        <div class="space-y-3">
          <!-- PDF canvas area (aspect ~ letter portrait) -->
          <Skeleton class="aspect-[8.5/11] w-full rounded-md" />

          <!-- Prev / Page x of y / Next footer -->
          <div class="flex items-center justify-center gap-3 pt-1">
            <Skeleton class="h-8 w-20 rounded-md" />
            <SkeletonLine width="1/4" height="sm" />
            <Skeleton class="h-8 w-20 rounded-md" />
          </div>
        </div>
      </SkeletonCard>
    </div>
  </div>
</template>
