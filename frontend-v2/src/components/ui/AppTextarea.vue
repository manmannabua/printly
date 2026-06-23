<script setup lang="ts">
import { computed } from 'vue'
import { Textarea } from '@/components/ui/textarea'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  modelValue?: string
  label?: string
  placeholder?: string
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
  readonly?: boolean
  rows?: number
  maxlength?: number
  showCount?: boolean
}>(), {
  rows: 3,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id || (props.label ? `textarea-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

const charCount = computed(() => props.modelValue?.length ?? 0)

function handleInput(event: Event): void {
  emit('update:modelValue', (event.target as HTMLTextAreaElement).value)
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
    <Textarea
      :id="inputId"
      :default-value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :readonly="readonly"
      :rows="rows"
      :maxlength="maxlength"
      :class="cn(
        'block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500',
        hasError
          ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400'
          : 'border-gray-300 hover:border-primary-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:hover:border-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400',
      )"
      :aria-invalid="hasError || undefined"
      :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
      @input="handleInput"
    />
    <div class="mt-1 flex items-center justify-between">
      <div>
        <p
          v-if="hasError"
          :id="`${inputId}-error`"
          class="text-sm text-danger-600 dark:text-danger-400"
        >
          {{ errorMessage }}
        </p>
        <p
          v-else-if="helpText"
          :id="`${inputId}-help`"
          class="text-sm text-gray-500 dark:text-gray-400"
        >
          {{ helpText }}
        </p>
      </div>
      <p
        v-if="showCount && maxlength"
        class="text-xs text-gray-400 dark:text-gray-500"
      >
        {{ charCount }}/{{ maxlength }}
      </p>
    </div>
  </div>
</template>
