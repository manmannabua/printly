<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useApiList } from '@/composables/useApiList'
import { useForm } from '@/composables/useForm'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import type { Fulfillment, PricingMode, Store, ProductType, Product, PriceRule, Quote } from '@/types/printly'
import type { Column } from '@/components/ui/AppDataTable.vue'
import { getStore } from '@/services/storeService'
import {
  listAllProductTypes, createProductType, updateProductType, deleteProductType,
  createProduct, updateProduct, deleteProduct,
  listPriceRules, createPriceRule, deletePriceRule, quoteProduct,
} from '@/services/catalogService'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppDataTable from '@/components/ui/AppDataTable.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import AppCurrencyInput from '@/components/ui/AppCurrencyInput.vue'
import AppRowActions from '@/components/ui/AppRowActions.vue'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog.vue'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const storeId = String(route.params.id)

const store = ref<Store | null>(null)
const allTypes = ref<ProductType[]>([])

const types = useApiList<ProductType>(`/api/v1/stores/${storeId}/product-types`, {
  defaultSort: { field: 'sort_order', dir: 'asc' },
})
const products = useApiList<Product>(`/api/v1/stores/${storeId}/products`, {
  defaultSort: { field: 'sort_order', dir: 'asc' },
})

const canEdit = auth.can('catalog.update') || auth.can('catalog.create')

function peso(cents: number | null | undefined): string {
  return '₱' + ((cents ?? 0) / 100).toLocaleString('en-US', { minimumFractionDigits: 2 })
}
function toCents(value: string | number | null | undefined): number {
  return Math.round(Number(value ?? 0) * 100)
}

async function loadTypes(): Promise<void> {
  allTypes.value = await listAllProductTypes(storeId)
}

onMounted(async () => {
  store.value = await getStore(storeId)
  await loadTypes()
})

// ── Product type CRUD ────────────────────────────────────────────────────────
const typeColumns: Column[] = [
  { key: 'name', label: 'Name' },
  { key: 'pricing_mode', label: 'Pricing' },
  { key: 'fulfillment', label: 'Fulfillment' },
  { key: 'is_active', label: 'Active' },
  { key: 'products_count', label: 'Products' },
  { key: 'actions', label: '', align: 'right' },
]
const pricingOptions: { label: string, value: PricingMode }[] = [
  { label: 'File-based (auto-priced paper)', value: 'file_based' },
  { label: 'Spec-based (quoted: tarp / shirt)', value: 'spec_based' },
]
const fulfillmentOptions: { label: string, value: Fulfillment }[] = [
  { label: 'Manual', value: 'manual' },
  { label: 'Auto (agent, phase 2)', value: 'auto' },
]

const showTypeForm = ref(false)
const editingType = ref<ProductType | null>(null)
const typeForm = useForm<{ name: string, pricing_mode: PricingMode, fulfillment: Fulfillment, is_active: boolean }>({
  name: '',
  pricing_mode: 'file_based',
  fulfillment: 'manual',
  is_active: true,
})

function openTypeCreate(): void {
  editingType.value = null
  typeForm.reset()
  showTypeForm.value = true
}
function openTypeEdit(t: ProductType): void {
  editingType.value = t
  typeForm.fields.name = t.name
  typeForm.fields.pricing_mode = t.pricing_mode
  typeForm.fields.fulfillment = t.fulfillment
  typeForm.fields.is_active = t.is_active
  typeForm.clearErrors()
  showTypeForm.value = true
}
async function submitType(): Promise<void> {
  const ok = await typeForm.submit(async (data) => {
    editingType.value
      ? await updateProductType(storeId, editingType.value.id, data)
      : await createProductType(storeId, data)
  })
  if (ok) {
    toast.success(editingType.value ? 'Product type updated.' : 'Product type created.')
    showTypeForm.value = false
    types.refresh(); loadTypes()
  }
}
const deletingType = ref<ProductType | null>(null)
const typeDeleteLoading = ref(false)
async function confirmDeleteType(): Promise<void> {
  if (!deletingType.value) return
  typeDeleteLoading.value = true
  try {
    await deleteProductType(storeId, deletingType.value.id)
    toast.success('Product type deleted.')
    deletingType.value = null
    types.refresh(); products.refresh(); loadTypes()
  } catch { toast.error('Failed to delete — it may still have products.') }
  finally { typeDeleteLoading.value = false }
}

// ── Product CRUD ─────────────────────────────────────────────────────────────
const productColumns: Column[] = [
  { key: 'name', label: 'Product' },
  { key: 'product_type', label: 'Type' },
  { key: 'base_price_cents', label: 'Base price' },
  { key: 'is_active', label: 'Active' },
  { key: 'actions', label: '', align: 'right' },
]
const showProductForm = ref(false)
const editingProduct = ref<Product | null>(null)
const productForm = useForm({ product_type_id: '', name: '', base_price: '0', is_active: true })

function typeOptions() {
  return allTypes.value.map((t) => ({ label: t.name, value: t.id }))
}
function openProductCreate(): void {
  editingProduct.value = null
  productForm.reset()
  productForm.fields.product_type_id = allTypes.value[0]?.id ?? ''
  showProductForm.value = true
}
function openProductEdit(p: Product): void {
  editingProduct.value = p
  productForm.fields.product_type_id = p.product_type_id
  productForm.fields.name = p.name
  productForm.fields.base_price = (p.base_price_cents / 100).toFixed(2)
  productForm.fields.is_active = p.is_active
  productForm.clearErrors()
  showProductForm.value = true
}
async function submitProduct(): Promise<void> {
  const ok = await productForm.submit(async (data) => {
    const payload = {
      product_type_id: data.product_type_id,
      name: data.name,
      base_price_cents: toCents(data.base_price),
      is_active: data.is_active,
    }
    editingProduct.value
      ? await updateProduct(storeId, editingProduct.value.id, payload)
      : await createProduct(storeId, payload)
  })
  if (ok) {
    toast.success(editingProduct.value ? 'Product updated.' : 'Product created.')
    showProductForm.value = false
    products.refresh()
  }
}
const deletingProduct = ref<Product | null>(null)
const productDeleteLoading = ref(false)
async function confirmDeleteProduct(): Promise<void> {
  if (!deletingProduct.value) return
  productDeleteLoading.value = true
  try {
    await deleteProduct(storeId, deletingProduct.value.id)
    toast.success('Product deleted.')
    deletingProduct.value = null
    products.refresh()
  } catch { toast.error('Failed to delete product.') }
  finally { productDeleteLoading.value = false }
}

function typeName(id: string): string {
  return allTypes.value.find((t) => t.id === id)?.name ?? '—'
}
function typeOf(id: string): ProductType | undefined {
  return allTypes.value.find((t) => t.id === id)
}

// ── Price rules ──────────────────────────────────────────────────────────────
const rulesProduct = ref<Product | null>(null)
const rules = ref<PriceRule[]>([])
const rulesLoading = ref(false)
const ruleForm = useForm({ attribute: 'color', match_value: '', modifier_type: 'per_page', amount: '0', multiplier: '1' })
const modifierOptions = [
  { label: 'Add per page', value: 'per_page' },
  { label: 'Add per job', value: 'per_job' },
  { label: 'Multiply', value: 'multiplier' },
]

async function openRules(p: Product): Promise<void> {
  rulesProduct.value = p
  ruleForm.reset()
  rulesLoading.value = true
  try { rules.value = await listPriceRules(storeId, p.id) }
  finally { rulesLoading.value = false }
}
async function addRule(): Promise<void> {
  if (!rulesProduct.value) return
  const ok = await ruleForm.submit(async (data) => {
    const isMultiplier = data.modifier_type === 'multiplier'
    await createPriceRule(storeId, rulesProduct.value!.id, {
      attribute: data.attribute,
      match_value: data.match_value,
      modifier_type: data.modifier_type as 'per_page' | 'per_job' | 'multiplier',
      amount_cents: isMultiplier ? null : toCents(data.amount),
      multiplier: isMultiplier ? Number(data.multiplier) : null,
    })
  })
  if (ok) {
    toast.success('Rule added.')
    rules.value = await listPriceRules(storeId, rulesProduct.value.id)
    ruleForm.reset()
  }
}
async function removeRule(rule: PriceRule): Promise<void> {
  if (!rulesProduct.value) return
  await deletePriceRule(storeId, rulesProduct.value.id, rule.id)
  rules.value = await listPriceRules(storeId, rulesProduct.value.id)
  toast.success('Rule removed.')
}
function ruleEffect(r: PriceRule): string {
  if (r.modifier_type === 'multiplier') return `× ${r.multiplier}`
  return `${(r.amount_cents ?? 0) >= 0 ? '+' : ''}${peso(r.amount_cents)} ${r.modifier_type === 'per_page' ? '/page' : '/job'}`
}

// ── Quote tester ─────────────────────────────────────────────────────────────
const quoteProductRef = ref<Product | null>(null)
const quoteForm = useForm({ page_count: 1, paper_size: '', color: 'bw', duplex: 'simplex', copies: 1 })
const quoteResult = ref<Quote | null>(null)
const quoteLoading = ref(false)
const colorOptions = [{ label: 'Black & white', value: 'bw' }, { label: 'Colored', value: 'color' }]
const duplexOptions = [{ label: 'Single-sided', value: 'simplex' }, { label: 'Double-sided', value: 'duplex' }]

function openQuote(p: Product): void {
  quoteProductRef.value = p
  quoteResult.value = null
  quoteForm.reset()
}
async function runQuote(): Promise<void> {
  if (!quoteProductRef.value) return
  quoteLoading.value = true
  try {
    quoteResult.value = await quoteProduct(storeId, quoteProductRef.value.id, {
      page_count: Number(quoteForm.fields.page_count),
      paper_size: quoteForm.fields.paper_size || undefined,
      color: quoteForm.fields.color,
      duplex: quoteForm.fields.duplex === 'duplex',
      copies: Number(quoteForm.fields.copies),
    })
  } catch { toast.error('Quote failed.') }
  finally { quoteLoading.value = false }
}
</script>

<template>
  <div>
    <AppPageHeader
      :title="store?.name ?? 'Catalog'"
      subtitle="Product types, products and pricing rules"
      :breadcrumbs="[{ label: 'Stores', to: '/stores' }, { label: store?.name ?? '…' }, { label: 'Catalog' }]"
    />

    <!-- Product types -->
    <AppCard no-padding>
      <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Product types</h2>
        <AppButton v-if="canEdit" size="sm" icon="plus" @click="openTypeCreate"><span>Add type</span></AppButton>
      </div>
      <AppDataTable
        flush
        :columns="typeColumns"
        :rows="(types.data as unknown as Record<string, unknown>[])"
        :loading="types.loading"
        empty-title="No product types"
        empty-description="Add a type like ‘Document printing’ (file-based) or ‘Tarpaulin’ (spec-based)."
        empty-icon="layers"
      >
        <template #cell-name="{ value }"><span class="font-medium">{{ value }}</span></template>
        <template #cell-pricing_mode="{ value }">
          <AppBadge :variant="value === 'file_based' ? 'info' : 'primary'">
            {{ value === 'file_based' ? 'File-based' : 'Spec-based' }}
          </AppBadge>
        </template>
        <template #cell-is_active="{ value }">
          <AppBadge :variant="value ? 'success' : 'neutral'">{{ value ? 'Yes' : 'No' }}</AppBadge>
        </template>
        <template #cell-products_count="{ value }">{{ value ?? 0 }}</template>
        <template #cell-actions="{ row }">
          <AppRowActions v-if="canEdit">
            <DropdownMenuItem @click="openTypeEdit(row as unknown as ProductType)">Edit</DropdownMenuItem>
            <DropdownMenuItem class="text-danger-600" @click="deletingType = row as unknown as ProductType">Delete</DropdownMenuItem>
          </AppRowActions>
        </template>
      </AppDataTable>
    </AppCard>

    <!-- Products -->
    <AppCard no-padding class="mt-6">
      <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Products</h2>
        <AppButton v-if="canEdit" size="sm" icon="plus" :disabled="!allTypes.length" @click="openProductCreate">
          <span>Add product</span>
        </AppButton>
      </div>
      <AppDataTable
        flush
        :columns="productColumns"
        :rows="(products.data as unknown as Record<string, unknown>[])"
        :loading="products.loading"
        empty-title="No products"
        empty-description="Add a product under a type, e.g. ‘Short bond B&W’."
        empty-icon="layout-grid"
      >
        <template #cell-name="{ value }"><span class="font-medium">{{ value }}</span></template>
        <template #cell-product_type="{ row }">{{ typeName((row as unknown as Product).product_type_id) }}</template>
        <template #cell-base_price_cents="{ value }">{{ peso(value as number) }}</template>
        <template #cell-is_active="{ value }">
          <AppBadge :variant="value ? 'success' : 'neutral'">{{ value ? 'Yes' : 'No' }}</AppBadge>
        </template>
        <template #cell-actions="{ row }">
          <AppRowActions>
            <DropdownMenuItem
              v-if="typeOf((row as unknown as Product).product_type_id)?.pricing_mode === 'file_based'"
              @click="openQuote(row as unknown as Product)"
            >Test quote</DropdownMenuItem>
            <DropdownMenuItem v-if="canEdit" @click="openRules(row as unknown as Product)">Price rules</DropdownMenuItem>
            <DropdownMenuItem v-if="canEdit" @click="openProductEdit(row as unknown as Product)">Edit</DropdownMenuItem>
            <DropdownMenuItem v-if="auth.can('catalog.delete')" class="text-danger-600" @click="deletingProduct = row as unknown as Product">Delete</DropdownMenuItem>
          </AppRowActions>
        </template>
      </AppDataTable>
    </AppCard>

    <!-- Product type form -->
    <AppModal v-model="showTypeForm" :title="editingType ? 'Edit product type' : 'New product type'">
      <div class="space-y-4">
        <AppInput v-model="typeForm.fields.name" label="Name" required :error="typeForm.getError('name')" />
        <AppSelect v-model="typeForm.fields.pricing_mode" label="Pricing mode" :options="pricingOptions" :error="typeForm.getError('pricing_mode')" />
        <AppSelect v-model="typeForm.fields.fulfillment" label="Fulfillment" :options="fulfillmentOptions" :error="typeForm.getError('fulfillment')" />
        <AppSelect v-model="typeForm.fields.is_active" label="Active" :options="[{ label: 'Active', value: true }, { label: 'Inactive', value: false }]" />
      </div>
      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="showTypeForm = false"><span>Cancel</span></AppButton>
        <AppButton :icon="editingType ? 'check' : 'plus'" :loading="typeForm.loading" @click="submitType">
          <span>{{ editingType ? 'Save' : 'Create' }}</span>
        </AppButton>
      </template>
    </AppModal>

    <!-- Product form -->
    <AppModal v-model="showProductForm" :title="editingProduct ? 'Edit product' : 'New product'">
      <div class="space-y-4">
        <AppSelect v-model="productForm.fields.product_type_id" label="Product type" :options="typeOptions()" :error="productForm.getError('product_type_id')" />
        <AppInput v-model="productForm.fields.name" label="Name" required :error="productForm.getError('name')" />
        <AppCurrencyInput
          v-model="productForm.fields.base_price"
          label="Base price"
          help-text="File-based: price per page. Spec-based: flat base price."
          :error="productForm.getError('base_price_cents')"
        />
        <AppSelect v-model="productForm.fields.is_active" label="Active" :options="[{ label: 'Active', value: true }, { label: 'Inactive', value: false }]" />
      </div>
      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="showProductForm = false"><span>Cancel</span></AppButton>
        <AppButton :icon="editingProduct ? 'check' : 'plus'" :loading="productForm.loading" @click="submitProduct">
          <span>{{ editingProduct ? 'Save' : 'Create' }}</span>
        </AppButton>
      </template>
    </AppModal>

    <!-- Price rules -->
    <AppModal :model-value="rulesProduct !== null" size="lg" :title="`Price rules — ${rulesProduct?.name}`" @update:model-value="(v) => { if (!v) rulesProduct = null }">
      <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        Rules adjust the base per-page price when the uploaded file matches an attribute
        (e.g. color = colored, paper_size = A4). Multipliers apply last (e.g. duplex × 0.9).
      </p>

      <div v-if="rulesLoading" class="py-6 text-center text-sm text-gray-400">Loading…</div>
      <table v-else class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-gray-400">
          <tr><th class="py-2">Attribute</th><th>Matches</th><th>Effect</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="r in rules" :key="r.id" class="border-t border-gray-100 dark:border-gray-700">
            <td class="py-2 font-mono text-xs">{{ r.attribute }}</td>
            <td class="font-mono text-xs">{{ r.match_value }}</td>
            <td>{{ ruleEffect(r) }}</td>
            <td class="text-right">
              <button class="text-danger-600 hover:underline" @click="removeRule(r)">Remove</button>
            </td>
          </tr>
          <tr v-if="!rules.length"><td colspan="4" class="py-4 text-center text-gray-400">No rules yet.</td></tr>
        </tbody>
      </table>

      <div v-if="canEdit" class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
        <AppInput v-model="ruleForm.fields.attribute" label="Attribute" help-text="color | paper_size | duplex" :error="ruleForm.getError('attribute')" />
        <AppInput v-model="ruleForm.fields.match_value" label="Matches value" help-text="e.g. color, A4, duplex" :error="ruleForm.getError('match_value')" />
        <AppSelect v-model="ruleForm.fields.modifier_type" label="Effect" :options="modifierOptions" />
        <AppCurrencyInput v-if="ruleForm.fields.modifier_type !== 'multiplier'" v-model="ruleForm.fields.amount" label="Amount" :error="ruleForm.getError('amount_cents')" />
        <AppInput v-else v-model="ruleForm.fields.multiplier" type="number" step="0.1" label="Multiplier" :error="ruleForm.getError('multiplier')" />
      </div>
      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="rulesProduct = null"><span>Close</span></AppButton>
        <AppButton v-if="canEdit" icon="plus" :loading="ruleForm.loading" @click="addRule"><span>Add rule</span></AppButton>
      </template>
    </AppModal>

    <!-- Quote tester -->
    <AppModal :model-value="quoteProductRef !== null" :title="`Test quote — ${quoteProductRef?.name}`" @update:model-value="(v) => { if (!v) quoteProductRef = null }">
      <div class="grid grid-cols-2 gap-3">
        <AppInput v-model="quoteForm.fields.page_count" type="number" min="1" label="Page count" />
        <AppInput v-model="quoteForm.fields.copies" type="number" min="1" label="Copies" />
        <AppInput v-model="quoteForm.fields.paper_size" label="Paper size" help-text="e.g. A4 (optional)" />
        <AppSelect v-model="quoteForm.fields.color" label="Color" :options="colorOptions" />
        <AppSelect v-model="quoteForm.fields.duplex" label="Sides" :options="duplexOptions" />
      </div>

      <div v-if="quoteResult" class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900/40">
        <div class="space-y-1 text-sm">
          <div v-for="(line, i) in quoteResult.breakdown" :key="i" class="flex justify-between text-gray-600 dark:text-gray-400">
            <span>{{ line.label }}</span>
            <span v-if="line.amount_cents !== undefined">{{ peso(line.amount_cents) }}</span>
            <span v-else-if="line.multiplier !== undefined">× {{ line.multiplier }}</span>
          </div>
        </div>
        <div class="mt-3 flex justify-between border-t border-gray-200 pt-3 text-base font-bold dark:border-gray-700">
          <span>Total</span><span>{{ peso(quoteResult.total_cents) }}</span>
        </div>
      </div>

      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="quoteProductRef = null"><span>Close</span></AppButton>
        <AppButton icon="calculator" :loading="quoteLoading" @click="runQuote"><span>Calculate</span></AppButton>
      </template>
    </AppModal>

    <!-- Delete confirms -->
    <AppConfirmDialog
      :model-value="deletingType !== null" danger title="Delete product type?"
      :message="`Delete “${deletingType?.name}”? Products under it will be removed too.`"
      confirm-label="Delete" :loading="typeDeleteLoading"
      @update:model-value="(v) => { if (!v) deletingType = null }" @confirm="confirmDeleteType"
    />
    <AppConfirmDialog
      :model-value="deletingProduct !== null" danger title="Delete product?"
      :message="`Delete “${deletingProduct?.name}” and its price rules?`"
      confirm-label="Delete" :loading="productDeleteLoading"
      @update:model-value="(v) => { if (!v) deletingProduct = null }" @confirm="confirmDeleteProduct"
    />
  </div>
</template>
