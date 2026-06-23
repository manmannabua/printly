<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  entries: unknown[]
  emptyMessage?: string
}>()

const emit = defineEmits<{
  (e: 'edit', index: number): void
  (e: 'remove', index: number): void
}>()

const confirmingIndex = ref<number | null>(null)

function requestRemove(index: number) {
  confirmingIndex.value = index
}

function confirmRemove() {
  if (confirmingIndex.value !== null) {
    emit('remove', confirmingIndex.value)
    confirmingIndex.value = null
  }
}

function cancelRemove() {
  confirmingIndex.value = null
}
</script>

<template>
  <div v-if="entries.length > 0" class="mt-4 space-y-2">
    <div
      v-for="(entry, index) in entries"
      :key="index"
      class="rounded-lg border bg-white dark:bg-zinc-800"
      :class="confirmingIndex === index ? 'border-red-200 dark:border-red-900/50' : 'border-gray-200 dark:border-zinc-700'"
    >
      <div class="flex items-center justify-between px-4 py-3">
        <div class="min-w-0 flex-1 text-sm text-gray-700 dark:text-gray-200">
          <slot name="summary" :entry="entry" :index="index" />
        </div>
        <div class="ml-3 flex shrink-0 items-center gap-1">
          <button
            v-if="confirmingIndex !== index"
            type="button"
            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-c-primary dark:text-gray-500 dark:hover:bg-zinc-700"
            title="Edit"
            @click="emit('edit', index)"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            v-if="confirmingIndex !== index"
            type="button"
            class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:text-gray-500 dark:hover:bg-red-900/20"
            title="Remove"
            @click="requestRemove(index)"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Inline confirm row -->
      <div
        v-if="confirmingIndex === index"
        class="flex items-center justify-between border-t border-red-100 bg-red-50 px-4 py-2.5 rounded-b-lg dark:border-red-900/30 dark:bg-red-900/20"
      >
        <p class="text-sm text-red-700 dark:text-red-400">Remove this entry?</p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-700"
            @click="cancelRemove"
          >
            Cancel
          </button>
          <button
            type="button"
            class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700"
            @click="confirmRemove"
          >
            Remove
          </button>
        </div>
      </div>
    </div>
  </div>
  <p v-else class="mt-4 text-sm text-gray-400 dark:text-gray-500">{{ emptyMessage || 'No entries added yet.' }}</p>
</template>
