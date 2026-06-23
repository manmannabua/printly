<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  sections?: number
  cols?: number
  hasReferenceCard?: boolean
}>(), {
  sections: 2,
  cols: 4,
  hasReferenceCard: false,
})

// Simulate varied field counts per section for natural look
const fieldCounts = [4, 3, 4, 2, 3, 4, 2, 3]
</script>

<template>
  <!-- Page header skeleton -->
  <div class="mb-6 flex items-center justify-between">
    <div class="space-y-2">
      <SkeletonLine width="1/4" height="xs" />
      <SkeletonLine width="1/3" height="lg" />
    </div>
    <div class="flex gap-2">
      <Skeleton class="h-9 w-20 rounded-md" />
      <Skeleton class="h-9 w-24 rounded-md" />
    </div>
  </div>

  <!-- Optional reference card (context card at top of form) -->
  <SkeletonCard v-if="hasReferenceCard" :title="true" class="mb-4">
    <div :class="`grid grid-cols-${Math.min(cols, 4)} gap-4`">
      <div v-for="i in cols" :key="i" class="space-y-1.5">
        <Skeleton class="h-3 w-16 rounded" />
        <Skeleton class="h-5 w-24 rounded" />
      </div>
    </div>
  </SkeletonCard>

  <!-- Form card -->
  <SkeletonCard>
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
      <div v-for="s in sections" :key="s" class="py-6 first:pt-0">
        <!-- Section title -->
        <div class="mb-4 space-y-1">
          <Skeleton class="h-4 w-36 rounded" />
          <Skeleton class="h-3 w-56 rounded" />
        </div>
        <!-- Field grid -->
        <div :class="`grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-${Math.min(cols, 4)}`">
          <div
            v-for="f in fieldCounts[(s - 1) % fieldCounts.length]"
            :key="f"
            class="space-y-1.5"
          >
            <Skeleton class="h-3 w-20 rounded" />
            <Skeleton class="h-9 w-full rounded-md" />
          </div>
        </div>
      </div>
    </div>

    <!-- Footer actions -->
    <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
      <Skeleton class="h-9 w-20 rounded-md" />
      <Skeleton class="h-9 w-28 rounded-md" />
    </div>
  </SkeletonCard>
</template>
