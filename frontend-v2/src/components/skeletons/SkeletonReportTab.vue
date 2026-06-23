<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  kpiCount?: number
  showChart?: boolean
  showTable?: boolean
  tableRows?: number
}>(), {
  kpiCount: 4,
  showChart: true,
  showTable: true,
  tableRows: 5,
})
</script>

<template>
  <div>
    <div
      v-if="kpiCount > 0"
      class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4"
    >
      <div
        v-for="n in kpiCount"
        :key="n"
        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <Skeleton class="mb-3 h-3 w-24 rounded" />
        <Skeleton class="mb-2 h-7 w-16 rounded" />
        <Skeleton class="h-3 w-20 rounded" />
      </div>
    </div>

    <div
      v-if="showChart"
      class="mt-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
      <Skeleton class="mb-4 h-4 w-40 rounded" />
      <Skeleton class="h-72 w-full rounded-md" />
    </div>

    <div
      v-if="showTable"
      class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
        <div class="flex gap-4">
          <Skeleton v-for="n in 5" :key="n" class="h-3 w-20 rounded" />
        </div>
      </div>
      <div
        v-for="r in tableRows"
        :key="r"
        class="border-b border-gray-100 px-4 py-3 last:border-0 dark:border-gray-700/50"
      >
        <div class="flex items-center gap-4">
          <Skeleton class="h-8 w-8 rounded-full" />
          <Skeleton class="h-3 w-32 rounded" />
          <Skeleton class="h-3 w-16 rounded" />
          <Skeleton class="h-3 w-16 rounded" />
          <Skeleton class="ml-auto h-3 w-12 rounded" />
        </div>
      </div>
    </div>
  </div>
</template>
