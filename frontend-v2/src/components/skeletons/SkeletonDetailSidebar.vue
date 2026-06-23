<script setup lang="ts">
import SkeletonLine from '@/components/ui/skeleton/SkeletonLine.vue'
import SkeletonAvatar from '@/components/ui/skeleton/SkeletonAvatar.vue'
import SkeletonCard from '@/components/ui/skeleton/SkeletonCard.vue'
import SkeletonDescList from '@/components/ui/skeleton/SkeletonDescList.vue'
import SkeletonTable from '@/components/ui/skeleton/SkeletonTable.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

withDefaults(defineProps<{
  hideAvatar?: boolean
  sidebarRows?: number
  sidebarActions?: number
  sidebarExtraCards?: number
  hasTabs?: boolean
  tabsCount?: number
  sections?: number
  rowsPerSection?: number
  colsPerSection?: number
  hasTable?: boolean
  tableCols?: number
  tableRows?: number
}>(), {
  hideAvatar: false,
  sidebarRows: 5,
  sidebarActions: 0,
  sidebarExtraCards: 0,
  hasTabs: false,
  tabsCount: 3,
  sections: 1,
  rowsPerSection: 2,
  colsPerSection: 4,
  hasTable: false,
  tableCols: 5,
  tableRows: 5,
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

  <!-- Sidebar-left + main-right -->
  <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start">
    <!-- Sidebar column -->
    <div class="flex flex-col gap-4 lg:w-72 lg:shrink-0">
      <!-- Primary sidebar card -->
      <SkeletonCard>
        <div class="flex flex-col items-center gap-3 text-center">
          <SkeletonAvatar v-if="!hideAvatar" size="xl" />
          <SkeletonLine width="2/3" height="md" />
          <SkeletonLine width="1/2" height="sm" />
          <div class="flex gap-1.5">
            <Skeleton class="h-5 w-14 rounded-full" />
            <Skeleton class="h-5 w-12 rounded-full" />
          </div>
        </div>

        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
          <div class="space-y-3">
            <div v-for="i in sidebarRows" :key="i" class="flex justify-between">
              <SkeletonLine width="1/3" height="xs" />
              <SkeletonLine width="1/3" height="xs" />
            </div>
          </div>
        </div>

        <div v-if="sidebarActions > 0" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700 space-y-2">
          <Skeleton v-for="i in sidebarActions" :key="i" class="h-9 w-full rounded-md" />
        </div>
      </SkeletonCard>

      <!-- Extra sidebar cards (InfoGrid placeholders) -->
      <SkeletonCard v-for="i in sidebarExtraCards" :key="`extra-${i}`">
        <div class="mb-3 flex items-center gap-2">
          <Skeleton class="h-3.5 w-3.5 rounded" />
          <SkeletonLine width="1/3" height="xs" />
        </div>
        <div class="space-y-2">
          <div v-for="j in 3" :key="j" class="flex justify-between">
            <SkeletonLine width="1/3" height="xs" />
            <SkeletonLine width="1/2" height="xs" />
          </div>
        </div>
      </SkeletonCard>
    </div>

    <!-- Main content -->
    <div class="min-w-0 flex-1 space-y-4">
      <!-- Optional tabs -->
      <div v-if="hasTabs" class="flex gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
        <Skeleton v-for="i in tabsCount" :key="i" class="h-9 w-24 rounded-md" />
      </div>

      <!-- InfoGrid sections inside one card -->
      <SkeletonCard>
        <div
          v-for="i in sections"
          :key="i"
          :class="i > 1 ? 'mt-6 border-t border-gray-200 pt-4 dark:border-gray-700' : ''"
        >
          <!-- section title with icon -->
          <div class="mb-3 flex items-center gap-2">
            <Skeleton class="h-3.5 w-3.5 rounded" />
            <SkeletonLine width="1/4" height="xs" />
          </div>
          <SkeletonDescList :rows="rowsPerSection" :cols="colsPerSection" />
        </div>
      </SkeletonCard>

      <!-- Optional table -->
      <SkeletonCard v-if="hasTable" no-padding>
        <div class="px-4 py-3">
          <SkeletonLine width="1/4" height="sm" />
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <SkeletonTable :columns="tableCols" :rows="tableRows" />
        </div>
      </SkeletonCard>
    </div>
  </div>
</template>
