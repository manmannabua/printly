<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps<{
  label: string
  options: { id: string; name: string }[]
  modelValue: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

const open = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

function toggle() {
  open.value = !open.value
}

function select(value: string | null) {
  emit('update:modelValue', value)
  open.value = false
}

function handleClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))

const selectedLabel = () => {
  if (!props.modelValue) return props.label
  const found = props.options.find((o) => o.id === props.modelValue)
  return found ? found.name : props.label
}
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <button
      type="button"
      class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-colors"
      :class="modelValue
        ? 'border-c-light bg-c-primary-light text-c-primary-text dark:bg-zinc-700 dark:border-zinc-600 dark:text-gray-200'
        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-200 dark:hover:bg-zinc-700'"
      @click="toggle"
    >
      {{ selectedLabel() }}
      <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="open"
        class="absolute left-0 z-10 mt-1 w-48 origin-top-left rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
      >
        <button
          type="button"
          class="flex w-full items-center px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-zinc-700"
          :class="{ 'font-medium text-c-primary': !modelValue }"
          @click="select(null)"
        >
          All {{ label }}
        </button>
        <button
          v-for="option in options"
          :key="option.id"
          type="button"
          class="flex w-full items-center px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-zinc-700"
          :class="{ 'font-medium text-c-primary': modelValue === option.id }"
          @click="select(option.id)"
        >
          {{ option.name }}
        </button>
      </div>
    </Transition>
  </div>
</template>
