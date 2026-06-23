<script setup lang="ts">
import SkeletonLine from './SkeletonLine.vue'

const props = withDefaults(defineProps<{
  rows?: number
  cols?: number
}>(), {
  rows: 4,
  cols: 2,
})

// Vary label/value widths to look natural
const labelWidths = ['1/3', '1/2', '1/4', '1/3', '1/2', '1/4'] as const
const valueWidths = ['2/3', '1/2', '3/4', '2/3', '3/4', '1/2'] as const

function labelWidth(i: number) {
  return labelWidths[i % labelWidths.length]
}
function valueWidth(i: number) {
  return valueWidths[i % valueWidths.length]
}
</script>

<template>
  <div
    class="grid gap-x-6 gap-y-5"
    :style="{ gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))` }"
  >
    <div v-for="i in rows * cols" :key="i" class="space-y-1.5">
      <!-- Label -->
      <SkeletonLine :width="labelWidth(i)" height="xs" />
      <!-- Value -->
      <SkeletonLine :width="valueWidth(i)" height="sm" />
    </div>
  </div>
</template>
