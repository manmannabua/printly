<script setup lang="ts">
import { computed, ref } from 'vue'
import { Input } from '@/components/ui/input'
import AppIcon from '@/components/common/AppIcon.vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  modelValue?: string | number
  label?: string
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  placeholder?: string
  error?: string | string[]
  helpText?: string
  icon?: string
  id?: string
  disabled?: boolean
  required?: boolean
  readonly?: boolean
  autocomplete?: string
  min?: string | number
  max?: string | number
  step?: string | number
}>(), {
  type: 'text',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const showPassword = ref(false)
const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => isPassword.value && showPassword.value ? 'text' : props.type)

const inputId = computed(() => props.id || (props.label ? `input-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

function handleInput(event: Event): void {
  const target = event.target as HTMLInputElement
  if (props.type === 'number') {
    const raw = target.value
    // Emit raw string while the user is mid-decimal (e.g. "5000." or "-")
    // so the controlled input doesn't strip the trailing dot before they finish typing.
    // Emit 0 for empty input to keep the model typed as number.
    if (raw === '') {
      emit('update:modelValue', 0)
    } else if (raw.endsWith('.') || raw === '-') {
      emit('update:modelValue', raw as unknown as number)
    } else {
      emit('update:modelValue', Number(raw))
    }
  } else {
    emit('update:modelValue', target.value)
  }
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
    <div class="relative">
      <div
        v-if="icon"
        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
      >
        <AppIcon :name="icon" :size="18" class="text-gray-400" />
      </div>
      <Input
        :id="inputId"
        :type="inputType"
        :default-value="modelValue != null ? String(modelValue) : undefined"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :autocomplete="autocomplete"
        :min="min"
        :max="max"
        :step="step"
        :class="cn(
          'block w-full rounded-lg border bg-white py-2.5 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500',
          icon ? 'pl-10' : 'pl-3',
          isPassword ? 'pr-10' : 'pr-3',
          hasError
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400'
            : 'border-gray-300 hover:border-primary-400 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:hover:border-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400',
        )"
        :aria-invalid="hasError || undefined"
        :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
        @input="handleInput"
      />
      <button
        v-if="isPassword"
        type="button"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        @click="showPassword = !showPassword"
      >
        <AppIcon :name="showPassword ? 'eye' : 'eye-off'" :size="18" />
      </button>
    </div>
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
