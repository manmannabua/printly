<script setup lang="ts">
import Skeleton from './Skeleton.vue'
import SkeletonLine from './SkeletonLine.vue'

const props = withDefaults(defineProps<{
  columns?: number
  rows?: number
  showHeader?: boolean
}>(), {
  columns: 5,
  rows: 6,
  showHeader: true,
})

// Vary cell widths per column to look natural
const colWidths = ['1/2', '3/4', '1/3', '2/3', '1/2', '1/4', '3/4', '1/2'] as const
function cellWidth(col: number) {
  return colWidths[col % colWidths.length]
}
</script>

<template>
  <div class="w-full overflow-hidden">
    <table class="w-full text-sm">
      <!-- Header -->
      <thead v-if="showHeader">
        <tr class="border-b border-gray-200 dark:border-gray-700">
          <th
            v-for="col in columns"
            :key="col"
            class="px-4 py-3 text-left"
          >
            <SkeletonLine width="1/2" height="xs" />
          </th>
        </tr>
      </thead>
      <!-- Body rows -->
      <tbody>
        <tr
          v-for="row in rows"
          :key="row"
          class="border-b border-gray-100 dark:border-gray-700/50"
        >
          <td
            v-for="col in columns"
            :key="col"
            class="px-4 py-3"
          >
            <SkeletonLine :width="cellWidth(col - 1)" height="sm" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
