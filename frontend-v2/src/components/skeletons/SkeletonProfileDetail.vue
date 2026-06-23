<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonAvatar from '@/components/ui/skeleton/SkeletonAvatar.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  hasTabs?: boolean
  tabsCount?: number
}>(), {
  hasTabs: false,
  tabsCount: 5,
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
    </div>
  </div>

  <!-- Two-column layout: sidebar + main -->
  <div class="mt-4 flex flex-col gap-4 lg:flex-row">
    <!-- Left sidebar card -->
    <SkeletonCard class="lg:w-72 lg:shrink-0">
      <div class="flex flex-col items-center gap-3 text-center">
        <SkeletonAvatar size="2xl" />
        <SkeletonLine width="2/3" height="md" />
        <SkeletonLine width="1/2" height="sm" />
        <SkeletonLine width="1/2" height="sm" />
        <div class="w-full space-y-3 pt-2">
          <div class="flex justify-between">
            <SkeletonLine width="1/3" height="xs" />
            <SkeletonLine width="1/3" height="xs" />
          </div>
          <div class="flex justify-between">
            <SkeletonLine width="1/3" height="xs" />
            <SkeletonLine width="1/3" height="xs" />
          </div>
          <div class="flex justify-between">
            <SkeletonLine width="1/3" height="xs" />
            <SkeletonLine width="1/3" height="xs" />
          </div>
          <div class="flex justify-between">
            <SkeletonLine width="1/3" height="xs" />
            <SkeletonLine width="1/3" height="xs" />
          </div>
        </div>
      </div>
    </SkeletonCard>

    <!-- Right main content -->
    <div class="min-w-0 flex-1 space-y-4">
      <!-- Optional tabs strip -->
      <div v-if="hasTabs" class="flex flex-wrap gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
        <Skeleton v-for="i in tabsCount" :key="i" class="h-9 w-24 rounded-md" />
      </div>

      <!-- Overview metric cards grid -->
      <SkeletonCard>
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div
            v-for="i in 6"
            :key="i"
            class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
          >
            <div class="mb-2 flex items-center gap-2">
              <Skeleton class="h-7 w-7 rounded-full" />
              <SkeletonLine width="1/2" height="xs" />
            </div>
            <SkeletonLine width="1/3" height="md" />
          </div>
        </div>
        <!-- Leave balances row -->
        <div class="flex flex-wrap gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
          <Skeleton v-for="i in 4" :key="i" class="h-6 w-24 rounded-full" />
        </div>
      </SkeletonCard>

      <!-- Personal Information card -->
      <SkeletonCard :title="true">
        <SkeletonDescList :rows="2" :cols="4" />
      </SkeletonCard>

      <!-- Contact Information card -->
      <SkeletonCard :title="true">
        <SkeletonDescList :rows="2" :cols="4" />
      </SkeletonCard>

      <!-- Employment Information card -->
      <SkeletonCard :title="true">
        <SkeletonDescList :rows="2" :cols="4" />
      </SkeletonCard>
    </div>
  </div>
</template>
