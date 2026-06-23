<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
}>()

const emit = defineEmits<{
  'update:page': [page: number]
}>()

// Build a compact page-number list with ellipses when there are many pages.
// Always show first, last, current, and the two adjacent on either side.
const visiblePages = computed<(number | '…')[]>(() => {
  const total = props.totalPages
  const current = props.currentPage

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }

  const pages: (number | '…')[] = [1]

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  if (start > 2) pages.push('…')
  for (let i = start; i <= end; i++) pages.push(i)
  if (end < total - 1) pages.push('…')

  pages.push(total)
  return pages
})

function go(page: number): void {
  if (page < 1 || page > props.totalPages || page === props.currentPage) return
  emit('update:page', page)
}
</script>

<template>
  <nav v-if="totalPages > 1" class="mt-8 flex items-center justify-center gap-1" aria-label="Pagination">
    <!-- Previous -->
    <button
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors enabled:hover:border-c-light enabled:hover:bg-gray-50 enabled:hover:text-c-primary disabled:opacity-40 dark:border-zinc-700 dark:text-gray-400 dark:enabled:hover:border-zinc-600 dark:enabled:hover:bg-zinc-800"
      :disabled="currentPage <= 1"
      :aria-label="'Previous page'"
      @click="go(currentPage - 1)"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <!-- Page numbers -->
    <template v-for="(page, idx) in visiblePages" :key="`p-${idx}`">
      <span
        v-if="page === '…'"
        class="inline-flex h-9 min-w-[2.25rem] items-center justify-center px-1 text-sm text-gray-400 dark:text-gray-500"
      >
        &hellip;
      </span>
      <button
        v-else
        type="button"
        :aria-current="page === currentPage ? 'page' : undefined"
        :class="[
          'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors',
          page === currentPage
            ? 'bg-c-primary text-white shadow-sm'
            : 'border border-gray-200 text-gray-600 hover:border-c-light hover:bg-gray-50 hover:text-c-primary dark:border-zinc-700 dark:text-gray-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800',
        ]"
        @click="go(page)"
      >
        {{ page }}
      </button>
    </template>

    <!-- Next -->
    <button
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors enabled:hover:border-c-light enabled:hover:bg-gray-50 enabled:hover:text-c-primary disabled:opacity-40 dark:border-zinc-700 dark:text-gray-400 dark:enabled:hover:border-zinc-600 dark:enabled:hover:bg-zinc-800"
      :disabled="currentPage >= totalPages"
      :aria-label="'Next page'"
      @click="go(currentPage + 1)"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </nav>
</template>
