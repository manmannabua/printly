<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'
</script>

<template>
  <div class="flex gap-6">
    <!-- Feed column (matches MyAnnouncementsPage lg:w-4/5 layout) -->
    <div class="w-full min-w-0 space-y-4 lg:w-4/5">
      <div
        v-for="n in 3"
        :key="n"
        class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <!-- Priority badge + date -->
        <div class="mb-3 flex items-center gap-2">
          <Skeleton class="h-5 w-16 rounded-full" />
          <Skeleton class="h-3 w-28 rounded" />
        </div>
        <!-- Title -->
        <Skeleton class="mb-2 h-5 w-3/4 rounded" />
        <!-- Body text lines -->
        <Skeleton class="mb-1.5 h-3 w-full rounded" />
        <Skeleton class="mb-1.5 h-3 w-full rounded" />
        <Skeleton class="mb-4 h-3 w-2/3 rounded" />
        <!-- Footer: avatar + author + reaction counts -->
        <div class="flex items-center gap-3">
          <Skeleton class="h-6 w-6 rounded-full" />
          <Skeleton class="h-3 w-24 rounded" />
          <Skeleton class="h-3 w-16 rounded" />
        </div>
      </div>
    </div>

    <!-- Timeline sidebar (desktop only, matches lg:w-1/5) -->
    <div class="hidden lg:block lg:w-1/5">
      <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <Skeleton class="mb-3 h-4 w-20 rounded" />
        <div class="space-y-3">
          <div v-for="n in 6" :key="n" class="flex items-center gap-2">
            <Skeleton class="h-2 w-2 flex-shrink-0 rounded-full" />
            <Skeleton class="h-3 w-full rounded" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
