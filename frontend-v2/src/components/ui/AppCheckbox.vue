<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'

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

const inputId = computed(() => props.id || (props.label ? `checkbox-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

function handleChange(checked: boolean): void {
  emit('update:modelValue', checked)
}
</script>

<template>
  <div>
    <label :for="inputId" class="flex items-center gap-3" :class="{ 'cursor-not-allowed opacity-50': disabled }">
      <Checkbox
        :id="inputId"
        :model-value="modelValue"
        :disabled="disabled"
        class="h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 focus:ring-offset-0 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-primary-500 dark:focus:ring-primary-400"
        @update:model-value="handleChange"
      />
      <div v-if="label || description">
        <span v-if="label" class="text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ label }}
        </span>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400">
          {{ description }}
        </p>
      </div>
    </label>
    <p v-if="errorMessage" class="mt-1 text-sm text-danger-600 dark:text-danger-400">
      {{ errorMessage }}
    </p>
  </div>
</template>
