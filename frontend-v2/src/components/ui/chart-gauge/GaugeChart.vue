<script setup lang="ts">
import { computed } from 'vue'
import { VisSingleContainer, VisDonut } from '@unovis/vue'
import { ChartContainer, type ChartConfig } from '@/components/ui/chart'

const props = withDefaults(defineProps<{
  value: number
  max?: number
  color?: string
  trackColor?: string
  label?: string
  sublabel?: string
  arcWidth?: number
}>(), {
  max: 100,
  arcWidth: 24,
})

const clamped = computed(() => Math.max(0, Math.min(props.value, props.max)))
const remaining = computed(() => Math.max(0, props.max - clamped.value))
const percent = computed(() => Math.round((clamped.value / props.max) * 100))

const data = computed(() => [
  { name: 'value', value: clamped.value },
  { name: 'remaining', value: remaining.value },
])

const config = computed<ChartConfig>(() => ({
  value: { label: props.label ?? 'Value', color: props.color ?? 'hsl(var(--chart-1))' },
  remaining: { label: 'Remaining', color: props.trackColor ?? 'var(--color-gray-200)' },
}))

const valueAccessor = (d: { value: number }) => d.value
const colorAccessor = computed(() => [
  props.color ?? 'var(--chart-1)',
  props.trackColor ?? 'var(--color-gray-200)',
])
</script>

<template>
  <ChartContainer :config="config" class="relative">
    <VisSingleContainer :data="data">
      <VisDonut
        :value="valueAccessor"
        :color="colorAccessor"
        :arc-width="arcWidth"
        :pad-angle="0"
      />
    </VisSingleContainer>
    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
      <span class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
        {{ label ?? `${percent}%` }}
      </span>
      <span v-if="sublabel" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
        {{ sublabel }}
      </span>
    </div>
  </ChartContainer>
</template>
