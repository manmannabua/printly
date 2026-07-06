<script setup lang="ts">
import { computed } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  src?: string | null
  alt?: string
  /** Full name — auto-split into initials. Convenience for API responses
   * that return `name` as a single string. Wins over firstName/lastName
   * when both are supplied. */
  name?: string | null
  firstName?: string
  lastName?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  statusColor?: string
}>(), {
  size: 'md',
  statusColor: undefined,
})

const initials = computed(() => {
  if (props.name) {
    const parts = props.name.trim().split(/\s+/).filter(Boolean)
    const first = parts[0]?.charAt(0) ?? ''
    const last = parts.length > 1 ? (parts[parts.length - 1]?.charAt(0) ?? '') : ''
    const out = (first + last).toUpperCase()
    if (out) return out
  }
  const f = props.firstName?.charAt(0) ?? ''
  const l = props.lastName?.charAt(0) ?? ''
  return (f + l).toUpperCase() || '?'
})

const sizeClasses: Record<string, string> = {
  xs: 'h-6 w-6 text-xs',
  sm: 'h-8 w-8 text-xs',
  md: 'h-10 w-10 text-sm',
  lg: 'h-12 w-12 text-base',
  xl: 'h-16 w-16 text-lg',
}

const indicatorSizeClasses: Record<string, string> = {
  xs: 'h-1.5 w-1.5 ring-1',
  sm: 'h-2 w-2 ring-1',
  md: 'h-2.5 w-2.5 ring-2',
  lg: 'h-3 w-3 ring-2',
  xl: 'h-3.5 w-3.5 ring-2',
}
</script>

<template>
  <div class="relative inline-flex shrink-0">
    <Avatar :class="cn('rounded-full', sizeClasses[size])">
      <AvatarImage
        v-if="src"
        :src="src"
        :alt="alt || name || `${firstName ?? ''} ${lastName ?? ''}`.trim()"
        class="rounded-full object-cover"
      />
      <AvatarFallback
        class="inline-flex items-center justify-center rounded-full bg-primary-100 font-medium text-primary-700 dark:bg-primary-900 dark:text-primary-300"
        :class="sizeClasses[size]"
      >
        {{ initials }}
      </AvatarFallback>
    </Avatar>
    <span
      v-if="statusColor"
      class="absolute bottom-0 right-0 rounded-full ring-white dark:ring-gray-800"
      :class="[indicatorSizeClasses[size], statusColor]"
    />
  </div>
</template>
