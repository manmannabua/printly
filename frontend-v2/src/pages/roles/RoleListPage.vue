<script setup lang="ts">
import { useApiList } from '@/composables/useApiList'
import type { Role } from '@/types/auth'
import type { Column } from '@/components/ui/AppDataTable.vue'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppSearchBar from '@/components/ui/AppSearchBar.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppDataTable from '@/components/ui/AppDataTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

const list = useApiList<Role>('/api/v1/roles', {
  syncUrl: true,
  defaultSort: { field: 'level', dir: 'asc' },
})

const columns: Column[] = [
  { key: 'display_name', label: 'Role', sortable: true },
  { key: 'name', label: 'Key' },
  { key: 'level', label: 'Level', sortable: true },
  { key: 'permissions_count', label: 'Permissions' },
  { key: 'users_count', label: 'Users' },
]
</script>

<template>
  <div>
    <AppPageHeader
      title="Roles"
      subtitle="Roles and their permission sets"
      :breadcrumbs="[{ label: 'Administration' }, { label: 'Roles' }]"
    />

    <AppSearchBar v-model="list.search" placeholder="Search roles..." />

    <AppCard no-padding class="mt-4">
      <AppDataTable
        flush
        :columns="columns"
        :rows="(list.data as unknown as Record<string, unknown>[])"
        :loading="list.loading"
        :sort-by="list.sortBy"
        :sort-dir="list.sortDir"
        empty-title="No roles found"
        empty-description="Try adjusting your search."
        empty-icon="shield-check"
        @sort="list.setSort"
      >
        <template #cell-display_name="{ value }"><span class="font-medium">{{ value }}</span></template>
        <template #cell-name="{ value }"><span class="font-mono text-xs">{{ value }}</span></template>
        <template #cell-permissions_count="{ value }"><span>{{ value ?? 0 }}</span></template>
        <template #cell-users_count="{ value }"><span>{{ value ?? 0 }}</span></template>
      </AppDataTable>
    </AppCard>

    <AppPagination
      v-if="list.paginationProps"
      v-bind="list.paginationProps"
      class="mt-4"
      @update:current-page="list.setPage"
      @update:per-page="list.setPerPage"
    />
  </div>
</template>
