<script setup lang="ts">
/**
 * @deprecated For read-only detail pages, use `InfoGrid` + `InfoGridItem` instead.
 * AppDescriptionList wraps every field in its own bordered card, which creates
 * sparse "big box, small text" layouts. InfoGrid renders a dense <dl> grid with
 * no per-field borders — matching the EmployeeDetailPage density pattern.
 * See CLAUDE.md → "Detail Page Density Pattern" and frontend-v2/src/pages/users/UserDetailPage.vue.
 */
export interface DescriptionItem {
  label: string
  value?: string | number | null
}

withDefaults(defineProps<{
  items: DescriptionItem[]
  columns?: 1 | 2 | 3 | 4
}>(), {
  columns: 2,
})

const gridClasses: Record<number, string> = {
  1: 'grid-cols-1',
  2: 'grid-cols-1 sm:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
}
</script>

<template>
  <dl class="grid gap-3" :class="gridClasses[columns]">
    <div v-for="item in items" :key="item.label"
         class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
      <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">
        {{ item.label }}
      </dt>
      <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
        <slot :name="item.label" :item="item">
          {{ item.value ?? '-' }}
        </slot>
      </dd>
    </div>
  </dl>
</template>
