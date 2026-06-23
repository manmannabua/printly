<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  modelValue: string
  placeholder?: string
  debounce?: number
  stacked?: boolean
}>(), {
  placeholder: 'Search...',
  debounce: 300,
  stacked: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = ref(props.modelValue)
let timer: ReturnType<typeof setTimeout> | null = null

watch(() => props.modelValue, (val) => {
  localValue.value = val
})

function handleInput(event: Event): void {
  const value = (event.target as HTMLInputElement).value
  localValue.value = value

  if (timer) clearTimeout(timer)
  timer = setTimeout(() => {
    emit('update:modelValue', value)
  }, props.debounce)
}

function clear(): void {
  localValue.value = ''
  if (timer) clearTimeout(timer)
  emit('update:modelValue', '')
}

onUnmounted(() => {
  if (timer) clearTimeout(timer)
})
</script>

<template>
  <div :class="stacked ? 'flex flex-col gap-3' : 'flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center'">
    <!-- Search input -->
    <div :class="stacked ? 'relative w-full' : 'relative w-full sm:min-w-0 sm:flex-1'">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="inline-block shrink-0 text-gray-400">
          <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <input
        type="search"
        :value="localValue"
        :placeholder="placeholder"
        class="app-search-input block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-8 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
        @input="handleInput"
      />
      <button
        v-if="localValue"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        aria-label="Clear search"
        @click="clear"
      >
        <AppIcon name="x-mark" :size="16" />
      </button>
    </div>

    <!-- Filter slot -->
    <div v-if="$slots.filters" class="flex flex-wrap items-center gap-2 shrink-0">
      <slot name="filters" />
    </div>
  </div>
</template>

<style>
/* Suppress the browser-native search clear button — we render our own X.
   Non-scoped because Vue's [data-v-*] scoping does not propagate into ::-webkit-* pseudos;
   the .app-search-input class scopes the rules to this component only. */
.app-search-input::-webkit-search-cancel-button,
.app-search-input::-webkit-search-decoration,
.app-search-input::-webkit-search-results-button,
.app-search-input::-webkit-search-results-decoration {
  -webkit-appearance: none;
  appearance: none;
  display: none;
  width: 0;
  height: 0;
}
.app-search-input::-ms-clear,
.app-search-input::-ms-reveal {
  display: none;
  width: 0;
  height: 0;
}
</style>
