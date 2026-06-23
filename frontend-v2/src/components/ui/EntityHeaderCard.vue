<script setup lang="ts">
export interface MetadataItem {
  label: string
  value?: string | number | null
}

withDefaults(defineProps<{
  title: string
  statusLabel?: string
  statusVariant?: string
  metadata?: MetadataItem[]
}>(), {
  statusLabel: undefined,
  statusVariant: 'neutral',
  metadata: () => [],
})
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
    <!-- Top row: title + status + actions -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
          {{ title }}
        </h2>
        <span
          v-if="statusLabel"
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="{
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': statusVariant === 'success',
            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': statusVariant === 'neutral',
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': statusVariant === 'danger',
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': statusVariant === 'warning',
            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': statusVariant === 'info',
          }"
        >
          {{ statusLabel }}
        </span>
      </div>
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
        <slot name="actions" />
      </div>
    </div>

    <!-- Metadata grid -->
    <dl v-if="metadata.length > 0" class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 lg:grid-cols-4">
      <div v-for="item in metadata" :key="item.label">
        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ item.label }}
        </dt>
        <dd class="mt-0.5 text-sm text-gray-900 dark:text-gray-100">
          <slot :name="item.label" :item="item">
            {{ item.value ?? '—' }}
          </slot>
        </dd>
      </div>
    </dl>
  </div>
</template>
