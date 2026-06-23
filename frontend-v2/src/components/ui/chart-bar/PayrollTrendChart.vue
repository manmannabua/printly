<script setup lang="ts">
import { computed } from 'vue'
import { VisXYContainer, VisGroupedBar, VisAxis, VisCrosshair, VisTooltip } from '@unovis/vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { ChartContainer, ChartTooltipContent, ChartLegendContent, componentToString, type ChartConfig } from '@/components/ui/chart'
import AppSelect from '@/components/ui/AppSelect.vue'

export interface PayrollMonth {
  month: string
  gross: number
  net: number
}

const props = withDefaults(defineProps<{
  data: PayrollMonth[]
  title?: string
  currency?: string
  year?: number
  availableYears?: number[]
}>(), {
  title: 'Payroll Cost Trend',
  currency: '₱',
})

const emit = defineEmits<{
  'update:year': [year: number]
}>()

// Use chart-1 and chart-4 for distinct contrast — same pattern as AttendanceTrendChart
const config: ChartConfig = {
  gross: { label: 'Gross', color: 'var(--chart-1)' },
  net:   { label: 'Net',   color: 'var(--chart-4)' },
}

const colorAccessor = ['var(--chart-1)', 'var(--chart-4)']

// Index of the last month that has actual data
const latestIndex = computed(() => {
  for (let i = props.data.length - 1; i >= 0; i--) {
    if ((props.data[i]?.gross ?? 0) > 0 || (props.data[i]?.net ?? 0) > 0) return i
  }
  return -1
})

const latest = computed(() =>
  latestIndex.value >= 0 ? props.data[latestIndex.value] ?? null : null
)
const previous = computed(() => {
  for (let i = latestIndex.value - 1; i >= 0; i--) {
    const d = props.data[i]
    if (d && (d.gross > 0 || d.net > 0)) return d
  }
  return null
})

function momChange(field: 'gross' | 'net'): number | null {
  if (!latest.value || !previous.value || previous.value[field] === 0) return null
  return ((latest.value[field] - previous.value[field]) / previous.value[field]) * 100
}

const grossChange = computed(() => momChange('gross'))
const netChange = computed(() => momChange('net'))

function formatCurrency(v: number) {
  return `${props.currency}${v.toLocaleString()}`
}

// Position the overlay cards so they sit above the latest data bar.
// The chart has 12 equal slots; the bar center is at (index + 0.5) / 12.
// Clamp so cards don't overflow the right edge.
const cardLeftPct = computed(() => {
  if (latestIndex.value < 0 || props.data.length === 0) return 0
  const center = (latestIndex.value + 0.5) / props.data.length * 100
  // Pull back a bit so the cards don't overflow right side
  return Math.min(center, 72)
})

const yearOptions = computed(() =>
  (props.availableYears ?? []).map((y) => ({ label: String(y), value: String(y) }))
)

// BarChart accessors
const x = (_d: PayrollMonth, i: number) => i
const y = [(d: PayrollMonth) => d.gross, (d: PayrollMonth) => d.net]

const xTickFormat = (i: number) => {
  const month = props.data[i]?.month ?? ''
  if (!month) return ''
  const [, m] = month.split('-')
  const date = new Date(2000, parseInt(m) - 1, 1)
  return date.toLocaleString('default', { month: 'short' })
}
const yTickFormat = (v: number) => `${props.currency}${(v / 1000).toFixed(0)}K`

const tooltipTemplate = computed(() =>
  componentToString(config, ChartTooltipContent),
)
</script>

<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between pb-2">
      <CardTitle>{{ title }}</CardTitle>
      <AppSelect
        v-if="year !== undefined && availableYears"
        :model-value="String(year)"
        :options="yearOptions"
        class="w-24"
        @update:model-value="emit('update:year', Number($event))"
      />
    </CardHeader>
    <CardContent>
      <!-- Bar chart with overlaid metric cards -->
      <div v-if="data.length > 0" class="relative">
        <!-- Metric cards anchored above the latest data bar -->
        <div
          v-if="latest"
          class="absolute top-1 z-10 flex gap-2"
          :style="{ left: `${cardLeftPct}%` }"
        >
          <Card class="shadow-md px-3 py-2 gap-0">
            <p class="text-xs text-muted-foreground whitespace-nowrap">Latest Gross</p>
            <p class="text-sm font-semibold tabular-nums text-foreground whitespace-nowrap">
              {{ formatCurrency(latest.gross) }}
            </p>
            <p
              v-if="grossChange !== null"
              class="text-xs font-medium whitespace-nowrap"
              :class="grossChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'"
            >
              {{ grossChange >= 0 ? '↑' : '↓' }} {{ Math.abs(grossChange).toFixed(1) }}% MoM
            </p>
          </Card>
          <Card class="shadow-md px-3 py-2 gap-0">
            <p class="text-xs text-muted-foreground whitespace-nowrap">Latest Net</p>
            <p class="text-sm font-semibold tabular-nums text-foreground whitespace-nowrap">
              {{ formatCurrency(latest.net) }}
            </p>
            <p
              v-if="netChange !== null"
              class="text-xs font-medium whitespace-nowrap"
              :class="netChange >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'"
            >
              {{ netChange >= 0 ? '↑' : '↓' }} {{ Math.abs(netChange).toFixed(1) }}% MoM
            </p>
          </Card>
        </div>

        <ChartContainer :config="config" class="h-64">
          <VisXYContainer :data="data" :margin="{ left: 8, right: 8, top: 4, bottom: 4 }">
            <VisGroupedBar
              :x="x"
              :y="y"
              :color="colorAccessor"
              :rounded-corners="3"
            />
            <VisAxis type="x" :tick-format="xTickFormat" :grid-line="false" />
            <VisAxis type="y" :tick-format="yTickFormat" />
            <VisCrosshair :template="tooltipTemplate" />
            <VisTooltip />
          </VisXYContainer>
          <ChartLegendContent />
        </ChartContainer>
      </div>
      <p v-else class="py-8 text-center text-sm text-muted-foreground">
        No payroll data available
      </p>
    </CardContent>
  </Card>
</template>
