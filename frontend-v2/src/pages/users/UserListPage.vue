<script setup lang="ts">
import { useApiList } from '@/composables/useApiList'
import type { User, Role } from '@/types/auth'
import type { Column } from '@/components/ui/AppDataTable.vue'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppSearchBar from '@/components/ui/AppSearchBar.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppDataTable from '@/components/ui/AppDataTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const list = useApiList<User>('/api/v1/users', {
  syncUrl: true,
  defaultSort: { field: 'created_at', dir: 'desc' },
})

const columns: Column[] = [
  { key: 'email', label: 'Email', sortable: true },
  { key: 'roles', label: 'Roles' },
  { key: 'is_active', label: 'Status' },
  { key: 'last_login_at', label: 'Last Login', sortable: true },
]

function formatDate(value: unknown): string {
  if (!value) return '—'
  return new Date(String(value)).toLocaleString()
}
</script>

<template>
  <div>
    <AppPageHeader
      title="Users"
      subtitle="System user accounts"
      :breadcrumbs="[{ label: 'Administration' }, { label: 'Users' }]"
    />

    <AppSearchBar v-model="list.search" placeholder="Search by email..." />

    <AppCard no-padding class="mt-4">
      <AppDataTable
        flush
        :columns="columns"
        :rows="(list.data as unknown as Record<string, unknown>[])"
        :loading="list.loading"
        :sort-by="list.sortBy"
        :sort-dir="list.sortDir"
        empty-title="No users found"
        empty-description="Try adjusting your search."
        empty-icon="users-group"
        @sort="list.setSort"
      >
        <template #cell-email="{ value }">
          <span class="font-medium">{{ value }}</span>
        </template>
        <template #cell-roles="{ row }">
          <div class="flex flex-wrap gap-1">
            <AppBadge v-for="r in ((row.roles as Role[]) ?? [])" :key="r.id" variant="info">
              {{ r.display_name ?? r.name }}
            </AppBadge>
            <span v-if="!((row.roles as Role[]) ?? []).length" class="text-gray-400">—</span>
          </div>
        </template>
        <template #cell-is_active="{ value }">
          <AppBadge :variant="value ? 'success' : 'neutral'">{{ value ? 'Active' : 'Inactive' }}</AppBadge>
        </template>
        <template #cell-last_login_at="{ value }">
          <span>{{ formatDate(value) }}</span>
        </template>
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
