<script setup lang="ts">
import { computed } from 'vue'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

export interface RadioOption {
  label: string
  value: string | number
  description?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<{
  modelValue?: string | number | null
  label?: string
  options: RadioOption[]
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
  direction?: 'vertical' | 'horizontal'
}>(), {
  direction: 'vertical',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const groupId = computed(() => props.id || (props.label ? `radio-${props.label.toLowerCase().replace(/\s+/g, '-')}` : 'radio-group'))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

function handleChange(value: unknown): void {
  if (typeof value !== 'string' && typeof value !== 'number') return
  emit('update:modelValue', value)
}
</script>

<template>
  <fieldset :aria-describedby="hasError ? `${groupId}-error` : helpText ? `${groupId}-help` : undefined">
    <legend
      v-if="label"
      class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300"
    >
      {{ label }}
      <span v-if="required" class="text-danger-500">*</span>
    </legend>
    <RadioGroup
      :model-value="modelValue != null ? String(modelValue) : undefined"
      :disabled="disabled"
      :class="direction === 'horizontal' ? 'flex flex-wrap gap-4' : 'space-y-2'"
      @update:model-value="handleChange"
    >
      <label
        v-for="option in options"
        :key="option.value"
        :for="`${groupId}-${option.value}`"
        class="flex items-start gap-3"
        :class="{ 'cursor-not-allowed opacity-50': disabled || option.disabled }"
      >
        <RadioGroupItem
          :id="`${groupId}-${option.value}`"
          :value="String(option.value)"
          :disabled="disabled || option.disabled"
          class="mt-0.5 h-4 w-4 shrink-0 border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500 focus:ring-offset-0 disabled:cursor-not-allowed dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-primary-500 dark:focus:ring-primary-400"
        />
        <div>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ option.label }}
          </span>
          <p v-if="option.description" class="text-sm text-gray-500 dark:text-gray-400">
            {{ option.description }}
          </p>
        </div>
      </label>
    </RadioGroup>
    <p
      v-if="hasError"
      :id="`${groupId}-error`"
      class="mt-1 text-sm text-danger-600 dark:text-danger-400"
    >
      {{ errorMessage }}
    </p>
    <p
      v-else-if="helpText"
      :id="`${groupId}-help`"
      class="mt-1 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ helpText }}
    </p>
  </fieldset>
</template>
