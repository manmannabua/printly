<script setup lang="ts">
import { computed } from 'vue'
import { VisSingleContainer, VisDonut, VisTooltip } from '@unovis/vue'
import { Donut } from '@unovis/ts'
import { ChartContainer, ChartLegendContent, type ChartConfig } from '@/components/ui/chart'

type DataRecord = Record<string, unknown>

const props = withDefaults(defineProps<{
  data: DataRecord[]
  index: string
  category: string
  colors?: string[]
  type?: 'donut' | 'pie'
  showLegend?: boolean
}>(), {
  colors: () => [],
  type: 'donut',
  showLegend: true,
})

const config = computed<ChartConfig>(() => {
  const cfg: ChartConfig = {}
  props.data.forEach((d, i) => {
    const label = String(d[props.index] ?? `Item ${i}`)
    cfg[label] = {
      label,
      color: props.colors[i] ?? `hsl(var(--chart-${(i % 5) + 1}))`,
    }
  })
  return cfg
})

const colorAccessor = computed(() =>
  props.data.map((d) => {
    const label = String(d[props.index] ?? '')
    return config.value[label]?.color ?? '#888'
  }),
)

const valueAccessor = (d: DataRecord) => Number(d[props.category]) || 0

const arcWidth = computed(() => props.type === 'pie' ? undefined : 60)

const triggers = computed(() => ({
  [`.${Donut.selectors.segment}`]: (d: unknown) => {
    const segment = d as { data?: Record<string, unknown>; value?: number }
    const label = String(segment.data?.[props.index] ?? '')
    const value = segment.value ?? 0
    const color = config.value[label]?.color ?? 'var(--chart-1)'
    return `
      <div class="rounded-lg border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl">
        <div class="flex items-center gap-2">
          <span class="size-2.5 rounded-[2px]" style="background:${color}"></span>
          <span class="font-medium text-foreground">${label}</span>
          <span class="ml-auto font-mono font-medium tabular-nums text-foreground">${value}</span>
        </div>
      </div>
    `
  },
}))
</script>

<template>
  <ChartContainer :config="config">
    <VisSingleContainer :data="data">
      <VisDonut
        :value="valueAccessor"
        :color="colorAccessor"
        :arc-width="arcWidth"
        :pad-angle="0.01"
      />
      <VisTooltip :triggers="triggers" />
    </VisSingleContainer>
    <ChartLegendContent v-if="showLegend" />
  </ChartContainer>
</template>
