<script setup lang="ts">
import { ref, computed, watch, onUnmounted, nextTick } from 'vue'

export interface AutocompleteOption {
  value: string | number
  label: string
}

const props = withDefaults(defineProps<{
  modelValue: string | number | null
  searchFn: (query: string) => Promise<AutocompleteOption[]>
  label?: string
  placeholder?: string
  error?: string | string[]
  helpText?: string
  id?: string
  disabled?: boolean
  required?: boolean
  initialDisplayValue?: string
  minChars?: number
  debounce?: number
  size?: 'sm' | 'md'
}>(), {
  minChars: 2,
  debounce: 300,
  size: 'md',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  'select': [option: AutocompleteOption | null]
}>()

const inputId = computed(() => props.id || (props.label ? `autocomplete-${props.label.toLowerCase().replace(/\s+/g, '-')}` : undefined))

const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) return props.error[0] ?? null
  return props.error
})

const hasError = computed(() => !!errorMessage.value)

const query = ref('')
const displayText = ref(props.initialDisplayValue ?? '')
const isOpen = ref(false)
const loading = ref(false)
const options = ref<AutocompleteOption[]>([])
const highlightedIndex = ref(-1)
const inputWrapperRef = ref<HTMLElement | null>(null)
const dropdownStyle = ref<Record<string, string>>({})

let debounceTimer: ReturnType<typeof setTimeout> | null = null
let blurTimer: ReturnType<typeof setTimeout> | null = null

const isSelected = computed(() => props.modelValue != null && props.modelValue !== '')

function updateDropdownPosition(): void {
  if (!inputWrapperRef.value) return
  const rect = inputWrapperRef.value.getBoundingClientRect()
  dropdownStyle.value = {
    position: 'fixed',
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
    zIndex: '9999',
  }
}

async function doSearch(term: string): Promise<void> {
  if (term.length < props.minChars) {
    options.value = []
    isOpen.value = false
    return
  }

  loading.value = true
  try {
    options.value = await props.searchFn(term)
    highlightedIndex.value = -1
    isOpen.value = true
    await nextTick()
    updateDropdownPosition()
  } catch {
    options.value = []
  } finally {
    loading.value = false
  }
}

function handleInput(event: Event): void {
  const value = (event.target as HTMLInputElement).value
  displayText.value = value
  query.value = value

  if (isSelected.value) {
    emit('update:modelValue', null)
  }

  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    doSearch(value)
  }, props.debounce)
}

function selectOption(option: AutocompleteOption): void {
  displayText.value = option.label
  emit('update:modelValue', option.value)
  emit('select', option)
  isOpen.value = false
  highlightedIndex.value = -1
  options.value = []
}

function clearSelection(): void {
  displayText.value = ''
  query.value = ''
  emit('update:modelValue', null)
  emit('select', null)
  options.value = []
  isOpen.value = false
}

function handleBlur(): void {
  blurTimer = setTimeout(() => {
    isOpen.value = false
    highlightedIndex.value = -1
  }, 150)
}

function handleFocus(): void {
  if (blurTimer) clearTimeout(blurTimer)
}

function handleKeydown(event: KeyboardEvent): void {
  if (!isOpen.value && event.key !== 'Escape') return

  switch (event.key) {
    case 'ArrowDown': {
      event.preventDefault()
      highlightedIndex.value = highlightedIndex.value < options.value.length - 1 ? highlightedIndex.value + 1 : 0
      break
    }
    case 'ArrowUp': {
      event.preventDefault()
      highlightedIndex.value = highlightedIndex.value > 0 ? highlightedIndex.value - 1 : options.value.length - 1
      break
    }
    case 'Enter': {
      event.preventDefault()
      const option = options.value[highlightedIndex.value]
      if (option) {
        selectOption(option)
      }
      break
    }
    case 'Escape': {
      event.preventDefault()
      isOpen.value = false
      highlightedIndex.value = -1
      break
    }
  }
}

function handleScrollOrResize(): void {
  if (isOpen.value) {
    updateDropdownPosition()
  }
}

watch(isOpen, (val) => {
  if (val) {
    window.addEventListener('scroll', handleScrollOrResize, true)
    window.addEventListener('resize', handleScrollOrResize)
  } else {
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
  }
})

watch(() => props.modelValue, (val) => {
  if (val == null || val === '') {
    displayText.value = ''
    query.value = ''
  }
})

watch(() => props.initialDisplayValue, (val) => {
  if (val != null) {
    displayText.value = val
  }
})

onUnmounted(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (blurTimer) clearTimeout(blurTimer)
  window.removeEventListener('scroll', handleScrollOrResize, true)
  window.removeEventListener('resize', handleScrollOrResize)
})
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
    <div ref="inputWrapperRef" class="relative">
      <!-- Search icon -->
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="inline-block shrink-0 text-gray-400">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <input
        :id="inputId"
        type="text"
        :value="displayText"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        role="combobox"
        autocomplete="off"
        :aria-expanded="isOpen"
        :aria-activedescendant="highlightedIndex >= 0 ? `${inputId}-option-${highlightedIndex}` : undefined"
        aria-haspopup="listbox"
        :aria-controls="isOpen ? `${inputId}-listbox` : undefined"
        class="block w-full rounded-lg border bg-white pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500"
        :class="[
          size === 'sm' ? 'py-1.5' : 'py-2.5',
          hasError
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-400 dark:focus:border-danger-400 dark:focus:ring-danger-400'
            : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:focus:border-primary-400 dark:focus:ring-primary-400',
        ]"
        :aria-invalid="hasError || undefined"
        :aria-describedby="hasError ? `${inputId}-error` : helpText ? `${inputId}-help` : undefined"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      />

      <!-- Loading spinner -->
      <div v-if="loading" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <svg class="h-4 w-4 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- Clear button -->
      <button
        v-else-if="isSelected"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        @mousedown.prevent
        @click="clearSelection"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="inline-block shrink-0">
          <path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </button>
    </div>

    <!-- Dropdown — teleported to body to escape overflow clipping -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="scale-95 opacity-0"
        enter-to-class="scale-100 opacity-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="scale-100 opacity-100"
        leave-to-class="scale-95 opacity-0"
      >
        <ul
          v-if="isOpen && !loading"
          :id="`${inputId}-listbox`"
          role="listbox"
          :style="dropdownStyle"
          class="max-h-60 overflow-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
          <li
            v-if="options.length === 0"
            class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400"
          >
            No results found
          </li>
          <li
            v-for="(option, index) in options"
            :id="`${inputId}-option-${index}`"
            :key="option.value"
            role="option"
            :aria-selected="highlightedIndex === index"
            class="cursor-pointer px-4 py-2 text-sm"
            :class="highlightedIndex === index ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-900 hover:bg-gray-50 dark:text-gray-100 dark:hover:bg-gray-700'"
            @mousedown.prevent
            @click="selectOption(option)"
          >
            {{ option.label }}
          </li>
        </ul>
      </Transition>
    </Teleport>

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
