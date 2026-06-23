<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
  modelValue?: string | number | null
  label?: string
  currency?: string
  symbol?: string
  placeholder?: string
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
  readonly?: boolean
  decimals?: number
  allowNegative?: boolean
}>(), {
  currency: 'PHP',
  decimals: 2,
  allowNegative: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const CURRENCY_SYMBOLS: Record<string, string> = {
  PHP: '₱', USD: '$', EUR: '€', GBP: '£', JPY: '¥', AUD: 'A$', CAD: 'C$', SGD: 'S$', HKD: 'HK$',
}

const inputId = computed(() => props.id || (props.label ? `currency-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

const currencySymbol = computed(() => props.symbol ?? CURRENCY_SYMBOLS[props.currency] ?? props.currency)

function clean(s: string): string {
  let r = s.replace(/[^\d.\-]/g, '')
  if (!props.allowNegative) r = r.replace(/-/g, '')
  // single leading minus
  if (r.includes('-')) {
    const wasNeg = r.startsWith('-')
    r = r.replace(/-/g, '')
    if (wasNeg) r = '-' + r
  }
  // single decimal
  const dotIdx = r.indexOf('.')
  if (dotIdx >= 0) {
    r = r.slice(0, dotIdx + 1) + r.slice(dotIdx + 1).replace(/\./g, '')
  }
  return r
}

function formatRaw(raw: string): string {
  if (raw === '' || raw === '-' || raw === '.' || raw === '-.') return raw
  const negative = raw.startsWith('-')
  const unsigned = negative ? raw.slice(1) : raw
  const dotIdx = unsigned.indexOf('.')
  const intStr = dotIdx === -1 ? unsigned : unsigned.slice(0, dotIdx)
  const intNum = Number(intStr || '0')
  let out = (negative ? '-' : '') + intNum.toLocaleString('en-US')
  if (dotIdx !== -1) {
    out += '.' + unsigned.slice(dotIdx + 1, dotIdx + 1 + props.decimals)
  }
  return out
}

const display = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

watch(() => props.modelValue, (val) => {
  const incoming = val == null ? '' : String(val)
  if (clean(display.value) === incoming) return
  display.value = formatRaw(clean(incoming))
}, { immediate: true })

function handleInput(event: Event): void {
  const target = event.target as HTMLInputElement
  const beforeValue = target.value
  const beforeCursor = target.selectionStart ?? beforeValue.length
  const digitsBeforeCursor = beforeValue.slice(0, beforeCursor).replace(/[^\d.\-]/g, '').length

  const cleaned = clean(beforeValue)
  const formatted = formatRaw(cleaned)

  display.value = formatted
  emit('update:modelValue', cleaned)

  // Restore cursor to keep the same digit-position in the formatted output.
  nextTick(() => {
    if (!inputRef.value) return
    let count = 0
    let newPos = formatted.length
    if (digitsBeforeCursor === 0) {
      newPos = 0
    } else {
      for (let i = 0; i < formatted.length; i++) {
        if (/[\d.\-]/.test(formatted.charAt(i))) count++
        if (count >= digitsBeforeCursor) { newPos = i + 1; break }
      }
    }
    inputRef.value.setSelectionRange(newPos, newPos)
  })
}

function handleBlur(): void {
  const cleaned = clean(display.value)
  if (cleaned === '' || cleaned === '-' || cleaned === '.' || cleaned === '-.') {
    display.value = ''
    emit('update:modelValue', '')
    return
  }
  const num = Number(cleaned)
  if (!isFinite(num)) {
    display.value = ''
    emit('update:modelValue', '')
    return
  }
  display.value = num.toLocaleString('en-US', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals,
  })
  emit('update:modelValue', num.toFixed(props.decimals))
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
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-gray-500 dark:text-gray-400">
        {{ currencySymbol }}
      </div>
      <input
        :id="inputId"
        ref="inputRef"
        type="text"
        inputmode="decimal"
        :value="display"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :class="cn(
          'block h-9 w-full min-w-0 rounded-md border bg-white py-1 pr-3 text-sm text-gray-900 placeholder-gray-400 shadow-xs outline-none transition-[color,box-shadow] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500',
          'hover:border-ring/60 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
          currencySymbol.length === 1 ? 'pl-8' : 'pl-12',
          hasError
            ? 'border-danger-500 ring-destructive/20 dark:border-danger-400 dark:ring-destructive/40'
            : 'border-gray-300 dark:border-gray-600',
        )"
        :aria-invalid="hasError || undefined"
        :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
        @input="handleInput"
        @blur="handleBlur"
      >
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
