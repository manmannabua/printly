<script setup lang="ts">
import SkeletonLine from './SkeletonLine.vue'

withDefaults(defineProps<{
  columns: number
  rows?: number
  hasActions?: boolean
}>(), {
  rows: 6,
  hasActions: false,
})

const colWidths = ['1/2', '3/4', '1/3', '2/3', '1/2', '1/4', '3/4', '1/2'] as const
function cellWidth(col: number): (typeof colWidths)[number] {
  return colWidths[col % colWidths.length]!
}
</script>

<template>
  <tr
    v-for="row in rows"
    :key="row"
  >
    <td
      v-for="col in columns"
      :key="col"
      class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3"
    >
      <SkeletonLine :width="cellWidth(col - 1)" height="sm" />
    </td>
    <td v-if="hasActions" class="whitespace-nowrap px-3 py-2.5 sm:px-4 sm:py-3">
      <SkeletonLine width="1/4" height="sm" />
    </td>
  </tr>
</template>
