<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: string
  label?: string
  placeholder?: string
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
  min?: string
  max?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id || (props.label ? `date-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

function handleInput(event: Event): void {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="inputId"
      class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
    >
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </label>
    <input
      :id="inputId"
      type="date"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :min="min"
      :max="max"
      class="block h-9 w-full rounded-lg border bg-white px-3 py-1 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100"
      :class="[
        hasError
          ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400'
          : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:focus:border-primary-400 dark:focus:ring-primary-400',
      ]"
      :aria-invalid="hasError || undefined"
      :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
      @input="handleInput"
    />
    <p
      v-if="hasError"
      :id="`${inputId}-error`"
      class="mt-1 text-sm text-danger-600 dark:text-danger-400"
    >
      {{ errorMessage }}
    </p>
    <p
      v-else-if="helpText"
      :id="`${inputId}-help`"
      class="mt-1 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ helpText }}
    </p>
  </div>
</template>
