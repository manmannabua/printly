<script setup lang="ts">
import AppCard from '@/components/ui/AppCard.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'

const props = withDefaults(defineProps<{
  title: string
  subtitle?: string | null
  caption?: string | null
  avatarSrc?: string | null
  avatarFirstName?: string
  avatarLastName?: string
  avatarIcon?: string
  avatarSize?: 'lg' | 'xl'
  statusColor?: string
  hideAvatar?: boolean
  width?: string
  sticky?: boolean
}>(), {
  subtitle: null,
  caption: null,
  avatarSrc: null,
  avatarSize: 'xl',
  hideAvatar: false,
  width: 'lg:w-72',
  sticky: true,
})

const stickyClasses = 'lg:sticky lg:top-20 lg:max-h-[calc(100vh-5rem)] lg:overflow-y-auto'
</script>

<template>
  <AppCard :class="[width, 'lg:shrink-0', props.sticky && stickyClasses]">
    <div class="flex flex-col items-center text-center">
      <div v-if="!hideAvatar" class="mb-3">
        <slot name="avatar">
          <AppAvatar
            :src="avatarSrc"
            :first-name="avatarFirstName"
            :last-name="avatarLastName"
            :status-color="statusColor"
            :size="avatarSize"
          />
        </slot>
      </div>
      <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h2>
      <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
      <p v-if="caption" class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ caption }}</p>
      <div v-if="$slots.tags" class="mt-2 flex flex-wrap justify-center gap-1.5">
        <slot name="tags" />
      </div>
    </div>

    <div v-if="$slots.info" class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
      <div class="space-y-3">
        <slot name="info" />
      </div>
    </div>

    <slot />
  </AppCard>
</template>
