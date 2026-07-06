<script setup lang="ts">
import { computed } from 'vue'

export interface SelectOption {
  label: string
  value: string | number | boolean | null
  disabled?: boolean
}

export interface SelectOptionGroup {
  label: string
  options: SelectOption[]
}

const props = defineProps<{
  modelValue?: string | number | boolean | null
  label?: string
  placeholder?: string
  options?: SelectOption[]
  optionGroups?: SelectOptionGroup[]
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number | boolean | null]
}>()

const flatOptions = computed(() => props.optionGroups
  ? props.optionGroups.flatMap((g) => g.options)
  : (props.options ?? []))

const inputId = computed(() => props.id || (props.label ? `select-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const showPlaceholderOption = computed(() => {
  if (!props.placeholder) return false
  return !flatOptions.value.some((o) => o.value === '' || o.value === null)
})

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

function handleChange(event: Event): void {
  const value = (event.target as HTMLSelectElement).value
  const option = flatOptions.value.find((opt) => String(opt.value) === value)
  emit('update:modelValue', option ? option.value : value === '' ? null : value)
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
    <select
      :id="inputId"
      :value="modelValue ?? ''"
      :disabled="disabled"
      :required="required"
      class="block h-9 w-full rounded-lg border bg-white py-1 pl-3 pr-10 text-sm text-gray-900 transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100"
      :class="[
        hasError
          ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400'
          : 'border-gray-300 hover:border-primary-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:hover:border-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400',
      ]"
      :aria-invalid="hasError || undefined"
      :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
      @change="handleChange"
    >
      <option v-if="showPlaceholderOption" value="" :disabled="required">{{ placeholder }}</option>
      <template v-if="optionGroups">
        <optgroup v-for="group in optionGroups" :key="group.label" :label="group.label">
          <option
            v-for="opt in group.options"
            :key="String(opt.value)"
            :value="opt.value"
            :disabled="opt.disabled"
          >
            {{ opt.label }}
          </option>
        </optgroup>
      </template>
      <template v-else>
        <option
          v-for="opt in options"
            :key="String(opt.value)"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </template>
    </select>
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
