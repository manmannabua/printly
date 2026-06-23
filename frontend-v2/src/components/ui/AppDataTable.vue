<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'
import AppEmptyState from '@/components/ui/AppEmptyState.vue'
import SkeletonTableRows from '@/components/ui/skeleton/SkeletonTableRows.vue'

export interface Column {
  key: string
  label: string
  sortable?: boolean
  sortKey?: string
  align?: 'left' | 'center' | 'right'
  width?: string
  hideOnMobile?: boolean
}

defineProps<{
  columns: Column[]
  rows: Record<string, unknown>[]
  loading?: boolean
  sortBy?: string
  sortDir?: 'asc' | 'desc'
  emptyTitle?: string
  emptyDescription?: string
  emptyIcon?: string
  rowClass?: (row: Record<string, unknown>) => string | undefined
  flush?: boolean
  clickableRows?: boolean
}>()

const emit = defineEmits<{
  sort: [field: string]
  'row-click': [row: Record<string, unknown>]
}>()

function handleSort(column: Column): void {
  if (!column.sortable) return
  emit('sort', column.sortKey ?? column.key)
}

function getCellValue(row: Record<string, unknown>, key: string): unknown {
  return key.includes('.') ? key.split('.').reduce((obj, k) => (obj as Record<string, unknown>)?.[k], row) : row[key]
}

const alignClasses: Record<string, string> = {
  left: 'text-left',
  center: 'text-center',
  right: 'text-right',
}
</script>

<template>
  <div class="relative overflow-hidden" :class="flush ? '' : 'rounded-lg border border-gray-200 dark:border-gray-700'">
    <!-- Indeterminate progress bar while re-loading with existing rows -->
    <div
      v-if="loading && rows.length > 0"
      class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-primary-100 dark:bg-primary-900/40"
    >
      <div class="h-full w-1/3 animate-[dataTableLoad_1.1s_ease-in-out_infinite] bg-primary-500 dark:bg-primary-400" />
    </div>

    <!-- Header actions slot -->
    <div v-if="$slots['header-actions']" class="border-b border-gray-200 bg-gray-50 px-3 py-2 sm:px-4 sm:py-3 dark:border-gray-700 dark:bg-gray-800/50">
      <slot name="header-actions" />
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 sm:px-4 sm:py-3 dark:text-gray-400"
              :class="[
                alignClasses[column.align ?? 'left'],
                column.sortable ? 'cursor-pointer select-none hover:text-gray-700 dark:hover:text-gray-300' : '',
                column.hideOnMobile ? 'hidden sm:table-cell' : '',
              ]"
              :style="column.width ? { width: column.width } : undefined"
              @click="handleSort(column)"
            >
              <div class="inline-flex items-center gap-1">
                {{ column.label }}
                <template v-if="column.sortable">
                  <AppIcon
                    v-if="sortBy === (column.sortKey ?? column.key)"
                    :name="sortDir === 'desc' ? 'chevron-down' : 'chevron-right'"
                    :size="14"
                    :class="sortDir === 'desc' ? 'rotate-0' : '-rotate-90'"
                  />
                  <span v-else class="inline-block w-3.5" />
                </template>
              </div>
            </th>
            <th v-if="$slots.actions" scope="col" class="relative w-10 px-3 py-2 sm:px-4 sm:py-3">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
          <template v-if="rows.length > 0">
            <tr
              v-for="(row, index) in rows"
              :key="(row.id as string) ?? index"
              class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
              :class="[rowClass?.(row), clickableRows ? 'cursor-pointer' : '']"
              @click="clickableRows ? emit('row-click', row) : undefined"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="whitespace-nowrap px-3 py-2.5 text-sm text-gray-900 sm:px-4 sm:py-3 dark:text-gray-100"
                :class="[alignClasses[column.align ?? 'left'], column.hideOnMobile ? 'hidden sm:table-cell' : '']"
              >
                <slot :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
                  {{ getCellValue(row, column.key) ?? '-' }}
                </slot>
              </td>
              <td v-if="$slots.actions" class="whitespace-nowrap px-3 py-2.5 text-right text-sm sm:px-4 sm:py-3" @click.stop>
                <slot name="actions" :row="row" />
              </td>
            </tr>
          </template>
          <SkeletonTableRows
            v-else-if="loading"
            :columns="columns.length"
            :has-actions="!!$slots.actions"
          />
          <tr v-else>
            <td :colspan="columns.length + ($slots.actions ? 1 : 0)">
              <slot name="empty">
                <AppEmptyState
                  :icon="emptyIcon ?? 'list'"
                  :title="emptyTitle ?? 'No data found'"
                  :description="emptyDescription"
                  compact
                />
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style>
@keyframes dataTableLoad {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(400%); }
}
</style>
