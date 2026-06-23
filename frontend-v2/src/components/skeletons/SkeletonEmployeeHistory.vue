<script setup lang="ts">
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  dayCount?: number
  eventsPerDay?: number
}>(), {
  dayCount: 4,
  eventsPerDay: 4,
})
</script>

<template>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
    <!-- 1/5: Sidebar card -->
    <div class="lg:col-span-1">
      <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col items-center text-center">
          <Skeleton class="mb-3 h-20 w-20 rounded-full" />
          <Skeleton class="mb-2 h-4 w-32 rounded" />
          <Skeleton class="mb-1 h-3 w-20 rounded" />
          <Skeleton class="mt-2 h-3 w-28 rounded" />
        </div>
        <div class="mt-4 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
          <div v-for="n in 4" :key="n" class="flex items-center justify-between">
            <Skeleton class="h-3 w-16 rounded" />
            <Skeleton class="h-3 w-20 rounded" />
          </div>
        </div>
      </div>
    </div>

    <!-- 3/5: Timeline sections -->
    <div class="space-y-6 lg:col-span-3">
      <section
        v-for="d in dayCount"
        :key="d"
        class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
      >
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <Skeleton class="h-4 w-40 rounded" />
            <Skeleton class="h-5 w-16 rounded-full" />
          </div>
          <Skeleton class="h-3 w-48 rounded" />
        </header>
        <div class="px-4 pb-4 pt-3">
          <ol class="relative space-y-4 border-l-2 border-gray-200 pl-6 dark:border-gray-700">
            <li v-for="e in eventsPerDay" :key="e" class="relative">
              <span class="absolute -left-[29px] top-1 h-3 w-3 rounded-full bg-gray-200 ring-4 ring-gray-100 dark:bg-gray-700 dark:ring-gray-800" />
              <div class="flex items-baseline justify-between gap-2">
                <Skeleton class="h-3 w-24 rounded" />
                <Skeleton class="h-3 w-16 rounded" />
              </div>
              <div class="mt-2 flex gap-1.5">
                <Skeleton class="h-3 w-10 rounded" />
                <Skeleton class="h-3 w-12 rounded" />
              </div>
            </li>
          </ol>
        </div>
      </section>
    </div>

    <!-- 1/5: Date rail -->
    <aside class="hidden lg:col-span-1 lg:block">
      <div class="space-y-2">
        <Skeleton class="mb-2 h-3 w-12 rounded" />
        <Skeleton v-for="n in 8" :key="n" class="h-6 w-full rounded" />
      </div>
    </aside>
  </div>
</template>
