<script setup lang="ts">
import { useApiList } from '@/composables/useApiList'
import type { Column } from '@/components/ui/AppDataTable.vue'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppSearchBar from '@/components/ui/AppSearchBar.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppDataTable from '@/components/ui/AppDataTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

interface AuditLogRow {
  id: string
  action: string
  auditable_type: string | null
  user?: { email?: string } | null
  created_at: string | null
}

const list = useApiList<AuditLogRow>('/api/v1/audit-logs', {
  syncUrl: true,
  defaultSort: { field: 'created_at', dir: 'desc' },
})

const columns: Column[] = [
  { key: 'created_at', label: 'When', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'auditable_type', label: 'Resource', sortable: true },
  { key: 'user', label: 'By' },
]

function formatDate(value: unknown): string {
  if (!value) return '—'
  return new Date(String(value)).toLocaleString()
}

function shortType(value: unknown): string {
  if (!value) return '—'
  const parts = String(value).split('\\')
  return parts[parts.length - 1] ?? String(value)
}
</script>

<template>
  <div>
    <AppPageHeader
      title="Audit Logs"
      subtitle="A record of changes made across the system"
      :breadcrumbs="[{ label: 'Administration' }, { label: 'Audit Logs' }]"
    />

    <AppSearchBar v-model="list.search" placeholder="Search by action or resource..." />

    <AppCard no-padding class="mt-4">
      <AppDataTable
        flush
        :columns="columns"
        :rows="(list.data as unknown as Record<string, unknown>[])"
        :loading="list.loading"
        :sort-by="list.sortBy"
        :sort-dir="list.sortDir"
        empty-title="No audit logs found"
        empty-description="Activity will appear here as changes are made."
        empty-icon="clipboard-list"
        @sort="list.setSort"
      >
        <template #cell-created_at="{ value }"><span>{{ formatDate(value) }}</span></template>
        <template #cell-action="{ value }"><AppBadge variant="neutral">{{ value }}</AppBadge></template>
        <template #cell-auditable_type="{ value }"><span>{{ shortType(value) }}</span></template>
        <template #cell-user="{ row }">
          <span>{{ (row.user as { email?: string })?.email ?? 'System' }}</span>
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
