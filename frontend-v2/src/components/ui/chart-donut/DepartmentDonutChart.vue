<script setup lang="ts">
import { computed } from 'vue'
import { VisSingleContainer, VisDonut, VisTooltip } from '@unovis/vue'
import { Donut } from '@unovis/ts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { ChartContainer, type ChartConfig } from '@/components/ui/chart'

export interface DeptSlice {
  name: string
  value: number
  fill: string
}

const props = defineProps<{
  data: DeptSlice[]
}>()

const total = computed(() => props.data.reduce((s, d) => s + d.value, 0))

const config = computed<ChartConfig>(() => {
  const cfg: ChartConfig = {}
  props.data.forEach((d) => {
    cfg[d.name] = { label: d.name, color: d.fill }
  })
  return cfg
})

const colorAccessor = computed(() => props.data.map((d) => d.fill))
const valueAccessor = (d: DeptSlice) => d.value

const triggers = computed(() => ({
  [`.${Donut.selectors.segment}`]: (raw: unknown) => {
    const seg = raw as { data?: DeptSlice; value?: number }
    const name = seg.data?.name ?? ''
    const val = seg.value ?? 0
    const pct = total.value > 0 ? ((val / total.value) * 100).toFixed(1) : '0'
    const color = seg.data?.fill ?? '#888'
    return `
      <div class="rounded-lg border border-border/50 bg-background px-2.5 py-1.5 text-xs shadow-xl">
        <div class="flex items-center gap-2">
          <span class="size-2.5 shrink-0 rounded-[2px]" style="background:${color}"></span>
          <span class="font-medium text-foreground">${name}</span>
          <span class="ml-auto font-mono font-medium tabular-nums text-foreground">${val} (${pct}%)</span>
        </div>
      </div>
    `
  },
}))
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Department Distribution</CardTitle>
    </CardHeader>
    <CardContent class="pb-2">
      <div v-if="data.length > 0">
        <!-- Donut chart -->
        <ChartContainer :config="config" class="relative mx-auto h-[200px] w-full max-w-[200px]">
          <VisSingleContainer :data="data">
            <VisDonut
              :value="valueAccessor"
              :color="colorAccessor"
              :arc-width="48"
              :pad-angle="0.015"
            />
            <VisTooltip :triggers="triggers" />
          </VisSingleContainer>
          <!-- Center label -->
          <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-2xl font-bold tabular-nums text-foreground">{{ total }}</span>
            <span class="text-xs text-muted-foreground">employees</span>
          </div>
        </ChartContainer>

        <!-- Legend -->
        <ul class="mt-4 space-y-1.5 px-1">
          <li
            v-for="slice in data"
            :key="slice.name"
            class="flex items-center gap-2 text-sm"
          >
            <span
              class="size-2.5 shrink-0 rounded-[2px]"
              :style="{ background: slice.fill }"
            />
            <span class="min-w-0 flex-1 truncate text-muted-foreground">{{ slice.name }}</span>
            <span class="font-semibold tabular-nums text-foreground">
              {{ total > 0 ? ((slice.value / total) * 100).toFixed(1) : '0' }}%
            </span>
          </li>
        </ul>
      </div>
      <p v-else class="py-10 text-center text-sm text-muted-foreground">
        No department data
      </p>
    </CardContent>
  </Card>
</template>
