<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  editingIndex: number | null
}>()

const emit = defineEmits<{
  (e: 'add'): void
  (e: 'save'): void
  (e: 'cancel'): void
}>()

const isEditing = computed(() => props.editingIndex !== null)
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
    <slot />

    <div class="mt-4 flex gap-2">
      <template v-if="isEditing">
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-c-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-c-primary-hover"
          @click="emit('save')"
        >
          Save Changes
        </button>
        <button
          type="button"
          class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-200 dark:hover:bg-zinc-600"
          @click="emit('cancel')"
        >
          Cancel
        </button>
      </template>
      <button
        v-else
        type="button"
        class="inline-flex items-center rounded-md bg-c-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-c-primary-hover"
        @click="emit('add')"
      >
        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Entry
      </button>
    </div>
  </div>
</template>
