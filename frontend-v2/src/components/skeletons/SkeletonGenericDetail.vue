<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  cards?: number
  rows?: number
  cols?: number
}>(), {
  cards: 2,
  rows: 4,
  cols: 2,
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

  <!-- N cards stacked -->
  <div class="space-y-4">
    <SkeletonCard v-for="i in cards" :key="i" :title="true">
      <SkeletonDescList :rows="rows" :cols="cols" />
    </SkeletonCard>
  </div>
</template>
