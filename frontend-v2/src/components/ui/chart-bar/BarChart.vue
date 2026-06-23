<script setup lang="ts">
import { computed } from 'vue'
import { VisXYContainer, VisGroupedBar, VisStackedBar, VisAxis, VisCrosshair, VisTooltip } from '@unovis/vue'
import { Orientation } from '@unovis/ts'
import { ChartContainer, ChartTooltipContent, ChartLegendContent, type ChartConfig } from '@/components/ui/chart'
import { componentToString } from '@/components/ui/chart'

type DataRecord = Record<string, unknown>

const props = withDefaults(defineProps<{
  data: DataRecord[]
  index: string
  categories: string[]
  colors?: string[]
  type?: 'grouped' | 'stacked'
  orientation?: 'horizontal' | 'vertical'
  showLegend?: boolean
  roundedCorners?: number
  xFormatter?: (value: string) => string
  yFormatter?: (value: number) => string
}>(), {
  colors: () => [],
  type: 'grouped',
  orientation: 'vertical',
  showLegend: true,
  roundedCorners: 4,
})

const config = computed<ChartConfig>(() => {
  const cfg: ChartConfig = {}
  props.categories.forEach((cat, i) => {
    cfg[cat] = {
      label: cat,
      color: props.colors[i] ?? `hsl(var(--chart-${(i % 5) + 1}))`,
    }
  })
  return cfg
})

const isHorizontal = computed(() => props.orientation === 'horizontal')
const orientationValue = computed(() =>
  isHorizontal.value ? Orientation.Horizontal : Orientation.Vertical,
)

const x = (_d: DataRecord, i: number) => i

const y = computed(() =>
  props.categories.map(cat => (d: DataRecord) => Number(d[cat]) || 0),
)

const colorAccessor = computed(() =>
  props.categories.map((cat) => config.value[cat]?.color ?? '#888'),
)

const xTickFormat = (i: number) => {
  const d = props.data[i]
  if (!d) return ''
  const raw = String(d[props.index] ?? '')
  return props.xFormatter ? props.xFormatter(raw) : raw
}

const yTickFormat = (v: number) => {
  return props.yFormatter ? props.yFormatter(v) : String(v)
}

const tooltipTemplate = computed(() =>
  componentToString(config.value, ChartTooltipContent),
)
</script>

<template>
  <ChartContainer :config="config">
    <VisXYContainer :data="data" :margin="{ left: 8, right: 8, top: 8 }">
      <VisStackedBar
        v-if="type === 'stacked'"
        :x="x"
        :y="y"
        :color="colorAccessor"
        :orientation="orientationValue"
        :rounded-corners="roundedCorners"
      />
      <VisGroupedBar
        v-else
        :x="x"
        :y="y"
        :color="colorAccessor"
        :orientation="orientationValue"
        :rounded-corners="roundedCorners"
      />
      <VisAxis
        :type="isHorizontal ? 'y' : 'x'"
        :tick-format="xTickFormat"
        :grid-line="false"
      />
      <VisAxis
        :type="isHorizontal ? 'x' : 'y'"
        :tick-format="yTickFormat"
      />
      <VisCrosshair :template="tooltipTemplate" />
      <VisTooltip />
    </VisXYContainer>
    <ChartLegendContent v-if="showLegend && categories.length > 1" />
  </ChartContainer>
</template>
