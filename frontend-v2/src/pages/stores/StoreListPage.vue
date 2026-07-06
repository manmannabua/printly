<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApiList } from '@/composables/useApiList'
import { useForm } from '@/composables/useForm'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import type { Store, StorePlan, StoreStatus } from '@/types/printly'
import type { Column } from '@/components/ui/AppDataTable.vue'
import { createStore, updateStore, deleteStore } from '@/services/storeService'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppSearchBar from '@/components/ui/AppSearchBar.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppDataTable from '@/components/ui/AppDataTable.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import AppRowActions from '@/components/ui/AppRowActions.vue'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog.vue'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const list = useApiList<Store>('/api/v1/stores', {
  syncUrl: true,
  defaultSort: { field: 'created_at', dir: 'desc' },
})

const columns: Column[] = [
  { key: 'name', label: 'Store', sortable: true },
  { key: 'plan', label: 'Plan', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'product_types_count', label: 'Types' },
  { key: 'products_count', label: 'Products' },
  { key: 'actions', label: '', align: 'right' },
]

const planOptions: { label: string, value: StorePlan }[] = [
  { label: 'Starter', value: 'starter' },
  { label: 'Pro', value: 'pro' },
  { label: 'Auto', value: 'auto' },
]
const statusOptions: { label: string, value: StoreStatus }[] = [
  { label: 'Trial', value: 'trial' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' },
]

const planVariant: Record<string, 'neutral' | 'info' | 'success'> = {
  starter: 'neutral', pro: 'info', auto: 'success',
}
const statusVariant: Record<string, 'neutral' | 'success' | 'danger'> = {
  trial: 'neutral', active: 'success', suspended: 'danger',
}

// ── Create / edit modal ──────────────────────────────────────────────────────
const showForm = ref(false)
const editing = ref<Store | null>(null)

const form = useForm<{
  name: string
  slug: string
  plan: StorePlan
  status: StoreStatus
  address: string
}>({
  name: '',
  slug: '',
  plan: 'starter',
  status: 'trial',
  address: '',
})

function slugify(value: string): string {
  return value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

function openCreate(): void {
  editing.value = null
  form.reset()
  showForm.value = true
}

function openEdit(store: Store): void {
  editing.value = store
  form.fields.name = store.name
  form.fields.slug = store.slug
  form.fields.plan = store.plan
  form.fields.status = store.status
  form.fields.address = store.address ?? ''
  form.clearErrors()
  showForm.value = true
}

async function submitForm(): Promise<void> {
  const ok = await form.submit(async (data) => {
    if (editing.value) {
      await updateStore(editing.value.id, data)
    } else {
      await createStore(data)
    }
  })
  if (ok) {
    toast.success(editing.value ? 'Store updated.' : 'Store created.')
    showForm.value = false
    list.refresh()
  }
}

// ── Delete ───────────────────────────────────────────────────────────────────
const deleting = ref<Store | null>(null)
const deleteLoading = ref(false)

async function confirmDelete(): Promise<void> {
  if (!deleting.value) return
  deleteLoading.value = true
  try {
    await deleteStore(deleting.value.id)
    toast.success('Store deleted.')
    deleting.value = null
    list.refresh()
  } catch {
    toast.error('Failed to delete store.')
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <div>
    <AppPageHeader
      title="Stores"
      subtitle="Printing businesses on the platform"
      :breadcrumbs="[{ label: 'Stores' }]"
    >
      <template #actions>
        <AppButton v-if="auth.can('stores.create')" icon="plus" @click="openCreate">
          <span>New Store</span>
        </AppButton>
      </template>
    </AppPageHeader>

    <AppSearchBar v-model="list.search" placeholder="Search stores..." />

    <AppCard no-padding class="mt-4">
      <AppDataTable
        flush
        :columns="columns"
        :rows="(list.data as unknown as Record<string, unknown>[])"
        :loading="list.loading"
        :sort-by="list.sortBy"
        :sort-dir="list.sortDir"
        empty-title="No stores yet"
        empty-description="Create your first store to start building its catalog."
        empty-icon="building"
        @sort="list.setSort"
      >
        <template #cell-name="{ row }">
          <button
            class="font-medium text-primary-600 hover:underline dark:text-primary-400"
            @click="router.push(`/stores/${(row as unknown as Store).id}/catalog`)"
          >
            {{ (row as unknown as Store).name }}
          </button>
          <p class="font-mono text-xs text-gray-400">/{{ (row as unknown as Store).slug }}</p>
        </template>
        <template #cell-plan="{ value }">
          <AppBadge :variant="planVariant[String(value)] ?? 'neutral'">{{ value }}</AppBadge>
        </template>
        <template #cell-status="{ value }">
          <AppBadge :variant="statusVariant[String(value)] ?? 'neutral'">{{ value }}</AppBadge>
        </template>
        <template #cell-product_types_count="{ value }">{{ value ?? 0 }}</template>
        <template #cell-products_count="{ value }">{{ value ?? 0 }}</template>
        <template #cell-actions="{ row }">
          <AppRowActions>
            <DropdownMenuItem @click="router.push(`/stores/${(row as unknown as Store).id}/catalog`)">
              Manage catalog
            </DropdownMenuItem>
            <DropdownMenuItem @click="router.push(`/stores/${(row as unknown as Store).id}/queue`)">
              Order queue
            </DropdownMenuItem>
            <DropdownMenuItem v-if="auth.can('stores.update')" @click="openEdit(row as unknown as Store)">
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem v-if="auth.can('stores.delete')" class="text-danger-600" @click="deleting = row as unknown as Store">
              Delete
            </DropdownMenuItem>
          </AppRowActions>
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

    <!-- Create / edit -->
    <AppModal v-model="showForm" :title="editing ? 'Edit Store' : 'New Store'">
      <div class="space-y-4">
        <AppInput
          v-model="form.fields.name"
          label="Store name"
          required
          :error="form.getError('name')"
          @update:model-value="!editing && (form.fields.slug = slugify(String($event)))"
        />
        <AppInput
          v-model="form.fields.slug"
          label="Slug"
          required
          help-text="Used in the storefront URL: /s/{slug}"
          :error="form.getError('slug')"
        />
        <div class="grid grid-cols-2 gap-4">
          <AppSelect v-model="form.fields.plan" label="Plan" :options="planOptions" :error="form.getError('plan')" />
          <AppSelect v-model="form.fields.status" label="Status" :options="statusOptions" :error="form.getError('status')" />
        </div>
        <AppInput v-model="form.fields.address" label="Address" :error="form.getError('address')" />
      </div>

      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="showForm = false">
          <span>Cancel</span>
        </AppButton>
        <AppButton :icon="editing ? 'check' : 'plus'" :loading="form.loading" @click="submitForm">
          <span>{{ editing ? 'Save' : 'Create' }}</span>
        </AppButton>
      </template>
    </AppModal>

    <!-- Delete -->
    <AppConfirmDialog
      :model-value="deleting !== null"
      danger
      title="Delete store?"
      :message="`This will remove “${deleting?.name}” and its catalog. This cannot be undone.`"
      confirm-label="Delete"
      :loading="deleteLoading"
      @update:model-value="(v) => { if (!v) deleting = null }"
      @confirm="confirmDelete"
    />
  </div>
</template>
