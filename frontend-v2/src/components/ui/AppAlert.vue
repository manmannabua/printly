<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

type AlertType = 'success' | 'warning' | 'danger' | 'info'

const props = withDefaults(defineProps<{
  type?: AlertType
  title?: string
  dismissible?: boolean
}>(), {
  type: 'info',
})

const emit = defineEmits<{
  dismiss: []
}>()

const dismissed = ref(false)

const alertType = computed<AlertType>(() => props.type)

const typeClasses: Record<AlertType, string> = {
  success: 'border-success-400 bg-success-50 text-success-800 dark:border-success-600 dark:bg-success-900/20 dark:text-success-300',
  warning: 'border-warning-400 bg-warning-50 text-warning-800 dark:border-warning-600 dark:bg-warning-900/20 dark:text-warning-300',
  danger: 'border-danger-400 bg-danger-50 text-danger-800 dark:border-danger-600 dark:bg-danger-900/20 dark:text-danger-300',
  info: 'border-info-400 bg-info-50 text-info-800 dark:border-info-600 dark:bg-info-900/20 dark:text-info-300',
}

const iconMap: Record<AlertType, string> = {
  success: 'check-circle',
  warning: 'exclamation-circle',
  danger: 'x-circle',
  info: 'info-circle',
}

function dismiss(): void {
  dismissed.value = true
  emit('dismiss')
}
</script>

<template>
  <div
    v-if="!dismissed"
    role="alert"
    class="flex items-start gap-3 rounded-lg border-l-4 p-4"
    :class="typeClasses[alertType]"
  >
    <AppIcon :name="iconMap[alertType]" :size="20" class="mt-0.5 shrink-0" />
    <div class="flex-1">
      <p v-if="title" class="font-medium">{{ title }}</p>
      <div class="text-sm" :class="{ 'mt-1': title }">
        <slot />
      </div>
    </div>
    <button
      v-if="dismissible"
      type="button"
      class="shrink-0 opacity-60 hover:opacity-100"
      aria-label="Dismiss"
      @click="dismiss"
    >
      <AppIcon name="x-circle" :size="16" />
    </button>
  </div>
</template>
