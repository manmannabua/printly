<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  subtitle?: string
  loading?: boolean
  error?: string | null
}>(), {
  title: 'Enter Security PIN',
  subtitle: 'Enter your 4-digit PIN to continue',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  submit: [pin: string]
}>()

const digits = ref(['', '', '', ''])
const inputRefs = ref<(HTMLInputElement | null)[]>([])

function setRef(index: number) {
  return (el: unknown) => {
    inputRefs.value[index] = el instanceof HTMLInputElement ? el : null
  }
}

const pin = ref('')

watch(digits, (vals) => {
  pin.value = vals.join('')
}, { deep: true })

watch(() => props.modelValue, (open) => {
  if (open) {
    digits.value = ['', '', '', '']
    nextTick(() => {
      inputRefs.value[0]?.focus()
    })
  }
})

watch(() => props.error, () => {
  if (props.error) {
    digits.value = ['', '', '', '']
    nextTick(() => {
      inputRefs.value[0]?.focus()
    })
  }
})

function handleInput(index: number, event: Event): void {
  const target = event.target as HTMLInputElement
  const value = target.value.replace(/\D/g, '').slice(0, 1)
  digits.value[index] = value
  if (value && index < 3) {
    nextTick(() => inputRefs.value[index + 1]?.focus())
  } else if (value && index === 3) {
    nextTick(() => handleSubmit())
  }
}

function handlePaste(index: number, event: ClipboardEvent): void {
  event.preventDefault()
  const text = event.clipboardData?.getData('text') ?? ''
  const chars = text.replace(/\D/g, '').slice(0, 4 - index).split('')
  chars.forEach((char, i) => {
    if (index + i < 4) {
      digits.value[index + i] = char
    }
  })
  const nextIndex = Math.min(index + chars.length, 3)
  nextTick(() => inputRefs.value[nextIndex]?.focus())
}

function handleKeydown(index: number, event: KeyboardEvent): void {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
    digits.value[index - 1] = ''
    nextTick(() => inputRefs.value[index - 1]?.focus())
  }
}

function handleSubmit(): void {
  if (pin.value.length === 4) {
    emit('submit', pin.value)
  }
}

</script>

<template>
  <AppModal
    :model-value="modelValue"
    size="sm"
    :closable="!loading"
    :persistent="loading"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="pb-4 text-center">
      <div class="mb-4 flex justify-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
          <AppIcon name="lock" :size="24" class="text-primary-600 dark:text-primary-400" />
        </div>
      </div>

      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
        {{ title }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ subtitle }}
      </p>

      <!-- PIN Input Boxes -->
      <div class="mt-6 flex items-center justify-center gap-3">
        <input
          v-for="(_, index) in 4"
          :key="index"
          :ref="setRef(index)"
          :value="digits[index]"
          type="password"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="1"
          class="h-14 w-14 rounded-lg border-2 border-gray-300 bg-white text-center text-2xl font-bold text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-primary-400"
          :disabled="loading"
          @input="handleInput(index, $event)"
          @keydown="handleKeydown(index, $event)"
          @paste="handlePaste(index, $event)"
        />
      </div>

      <!-- Error -->
      <p v-if="error" class="mt-3 text-sm text-red-600 dark:text-red-400">
        {{ error }}
      </p>
    </div>

  </AppModal>
</template>
