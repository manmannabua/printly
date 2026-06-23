<script setup lang="ts" generic="T">
import draggable from 'vuedraggable'

const props = defineProps<{
  entries: T[]
  maxEntries: number
  label: string
  addLabel: string
}>()

defineEmits<{
  (e: 'add'): void
  (e: 'remove', index: number): void
  (e: 'reorder', entries: T[]): void
}>()

// vuedraggable uses v-model but we need to emit back
function onDragEnd() {
  // entries array is already mutated in-place by vuedraggable
}
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-sm font-medium text-gray-700">{{ label }} ({{ entries.length }})</h3>
      <button
        v-if="entries.length < maxEntries"
        type="button"
        class="inline-flex items-center gap-1 text-sm font-medium text-c-primary hover:text-c-primary-hover"
        @click="$emit('add')"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ addLabel }}
      </button>
    </div>

    <p v-if="entries.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-8 text-center text-sm text-gray-500">
      No entries yet. Click "{{ addLabel }}" to get started.
    </p>

    <draggable
      v-if="entries.length > 0"
      :list="entries"
      handle=".drag-handle"
      item-key="index"
      ghost-class="opacity-30"
      drag-class="shadow-lg"
      class="space-y-3"
      @end="onDragEnd"
    >
      <template #item="{ element, index }">
        <div class="group relative rounded-lg border border-gray-200 bg-white p-4 transition-shadow hover:shadow-sm">
          <!-- Drag handle + Remove button -->
          <div class="absolute right-2 top-2 flex items-center gap-1">
            <button
              v-if="entries.length > 1"
              type="button"
              class="drag-handle cursor-grab p-1 text-gray-300 hover:text-gray-500 active:cursor-grabbing"
              title="Drag to reorder"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
              </svg>
            </button>
            <button
              type="button"
              class="p-1 text-red-300 hover:text-red-500"
              title="Remove"
              @click="$emit('remove', index)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <slot :entry="element" :index="index" />
        </div>
      </template>
    </draggable>

    <p v-if="entries.length >= maxEntries" class="mt-2 text-xs text-gray-500">
      Maximum of {{ maxEntries }} entries reached.
    </p>
  </div>
</template>
