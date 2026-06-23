<script setup lang="ts">
import { computed } from 'vue'
import { Switch } from '@/components/ui/switch'

const props = defineProps<{
  modelValue?: boolean
  label?: string
  description?: string
  error?: string | string[]
  id?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const inputId = computed(() => props.id || (props.label ? `toggle-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

function toggle(newValue?: boolean | Event): void {
  if (props.disabled) return
  emit('update:modelValue', typeof newValue === 'boolean' ? newValue : !props.modelValue)
}
</script>

<template>
  <div>
    <div class="flex items-start gap-3" :class="{ 'cursor-not-allowed opacity-50': disabled }">
      <Switch
        :id="inputId"
        :model-value="!!modelValue"
        :disabled="disabled"
        :aria-label="label"
        class="mt-0.5 shrink-0"
        @update:model-value="toggle"
      />
      <div v-if="label || description" class="cursor-pointer select-none" @click="toggle">
        <span v-if="label" class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ label }}
        </span>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400">
          {{ description }}
        </p>
      </div>
    </div>
    <p v-if="errorMessage" class="mt-1 text-sm text-danger-600 dark:text-danger-400">
      {{ errorMessage }}
    </p>
  </div>
</template>
