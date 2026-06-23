<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'
import { IconTrendingUp, IconTrendingDown } from '@tabler/icons-vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardAction, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  label: string
  value: string | number
  icon?: string
  variant?: 'default' | 'success' | 'warning' | 'danger'
  delta?: number
  deltaLabel?: string
  description?: string
  to?: RouteLocationRaw
}>(), {
  variant: 'default',
})

const router = useRouter()

const ACCENTS: Record<string, string> = {
  default: 'text-primary-600 dark:text-primary-400',
  success: 'text-emerald-600 dark:text-emerald-400',
  warning: 'text-amber-600 dark:text-amber-400',
  danger: 'text-rose-600 dark:text-rose-400',
}

const hasDelta = computed(() => props.delta !== undefined && props.delta !== 0)
const deltaUp = computed(() => (props.delta ?? 0) > 0)
const deltaText = computed(() => hasDelta.value ? `${deltaUp.value ? '+' : '-'}${Math.abs(props.delta!)}` : '')

function handleClick(): void {
  if (props.to) router.push(props.to)
}
</script>

<template>
  <Card
    class="@container/card gap-2 transition-shadow"
    :class="to ? 'cursor-pointer hover:border-gray-300 hover:shadow-md dark:hover:border-gray-600' : ''"
    :role="to ? 'link' : undefined"
    @click="handleClick"
  >
    <CardHeader>
      <CardDescription>{{ label }}</CardDescription>
      <CardTitle class="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
        <span class="flex items-center gap-2">
          <AppIcon v-if="icon" :name="icon" :size="20" :class="ACCENTS[variant]" />
          {{ value }}
        </span>
      </CardTitle>
      <CardAction v-if="hasDelta">
        <Badge variant="outline">
          <IconTrendingUp v-if="deltaUp" />
          <IconTrendingDown v-else />
          {{ deltaText }}
        </Badge>
      </CardAction>
    </CardHeader>
    <CardFooter v-if="description || hasDelta" class="flex-col items-start gap-1 text-sm">
      <div v-if="hasDelta" class="line-clamp-1 flex items-center gap-1.5 font-medium">
        {{ deltaUp ? 'Trending up' : 'Trending down' }} {{ deltaLabel }}
        <IconTrendingUp v-if="deltaUp" class="size-4" />
        <IconTrendingDown v-else class="size-4" />
      </div>
      <div v-else-if="description" class="text-muted-foreground">{{ description }}</div>
    </CardFooter>
  </Card>
</template>
