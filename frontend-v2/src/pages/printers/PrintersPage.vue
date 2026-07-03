<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useIntervalFn } from '@vueuse/core'
import { useRoute } from 'vue-router'
import { useForm } from '@/composables/useForm'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import type { PrintAgent, Printer, PrintJob, Store } from '@/types/printly'
import { subscribeToStoreOrders } from '@/services/echo'
import { getStore, updateStore } from '@/services/storeService'
import {
  listAgents, createAgent, regenerateAgentToken, deleteAgent,
  listPrinters, createPrinter, updatePrinter, deletePrinter,
  listJobs, retryJob,
} from '@/services/printerService'
import AppPageHeader from '@/components/ui/AppPageHeader.vue'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppRowActions from '@/components/ui/AppRowActions.vue'
import AppConfirmDialog from '@/components/ui/AppConfirmDialog.vue'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'

const route = useRoute()
const toast = useToast()
const auth = useAuthStore()
const storeId = String(route.params.id)

const canManage = computed(() => auth.can('printers.manage'))
const PAPER_SIZES = ['A4', 'Letter', 'Legal']

const store = ref<Store | null>(null)
const agents = ref<PrintAgent[]>([])
const printers = ref<Printer[]>([])
const jobs = ref<PrintJob[]>([])
const loading = ref(true)
const autoPrint = ref(false)

async function refresh() {
  const [s, a, p, j] = await Promise.all([
    getStore(storeId), listAgents(storeId), listPrinters(storeId), listJobs(storeId),
  ])
  store.value = s
  autoPrint.value = Boolean((s.settings as Record<string, unknown>)?.auto_print)
  agents.value = a
  printers.value = p
  jobs.value = j
}

// Live job status: real-time when Reverb is on, a gentle poll otherwise.
let unsubscribe: (() => void) | null = null
const { pause: stopPolling } = useIntervalFn(() => { refresh().catch(() => {}) }, 10000)

onMounted(async () => {
  try { await refresh() } catch { toast.error('Could not load printers.') } finally { loading.value = false }
  unsubscribe = subscribeToStoreOrders(storeId, {
    onPrintJobUpdated: () => { refresh().catch(() => {}) },
  })
})

onUnmounted(() => {
  stopPolling()
  unsubscribe?.()
})

// ── Auto-print toggle ────────────────────────────────────────────────────────
async function toggleAutoPrint(value: boolean) {
  if (!store.value) return
  autoPrint.value = value
  try {
    await updateStore(storeId, {
      settings: { ...(store.value.settings as Record<string, unknown>), auto_print: value },
    })
    store.value.settings = { ...(store.value.settings as Record<string, unknown>), auto_print: value }
    toast.success(value ? 'Auto-print enabled.' : 'Auto-print disabled.')
  } catch {
    autoPrint.value = !value
    toast.error('Could not update auto-print.')
  }
}

// ── Agents ───────────────────────────────────────────────────────────────────
const showAgentForm = ref(false)
const agentForm = useForm<{ name: string }>({ name: '' })
const deletingAgent = ref<PrintAgent | null>(null)
const revealedToken = ref<string | null>(null)

function openAgentForm() { agentForm.reset(); showAgentForm.value = true }

async function submitAgent() {
  const ok = await agentForm.submit(async (data) => {
    const created = await createAgent(storeId, data)
    revealedToken.value = created.token ?? null
    return created
  })
  if (ok) { showAgentForm.value = false; toast.success('Agent created.'); await refresh() }
}

async function regenerate(agent: PrintAgent) {
  try {
    const updated = await regenerateAgentToken(storeId, agent.id)
    revealedToken.value = updated.token ?? null
    toast.success('New token issued.')
  } catch { toast.error('Could not regenerate token.') }
}

async function confirmDeleteAgent() {
  if (!deletingAgent.value) return
  try { await deleteAgent(storeId, deletingAgent.value.id); toast.success('Agent removed.'); await refresh() }
  catch { toast.error('Could not remove agent.') }
  finally { deletingAgent.value = null }
}

async function copyToken() {
  if (!revealedToken.value) return
  try { await navigator.clipboard.writeText(revealedToken.value); toast.success('Token copied.') } catch { /* noop */ }
}

// ── Printers ─────────────────────────────────────────────────────────────────
type PrinterFields = { name: string; print_agent_id: string | null; sizes: string[]; color: boolean; is_active: boolean }
const showPrinterForm = ref(false)
const editingPrinter = ref<Printer | null>(null)
const printerForm = useForm<PrinterFields>({ name: '', print_agent_id: null, sizes: [...PAPER_SIZES], color: false, is_active: true })
const deletingPrinter = ref<Printer | null>(null)

const agentOptions = computed(() => [
  { label: 'Unassigned', value: '' },
  ...agents.value.map((a) => ({ label: a.name, value: a.id })),
])

function openPrinterCreate() {
  editingPrinter.value = null
  printerForm.reset()
  printerForm.fields.print_agent_id = agents.value[0]?.id ?? null
  showPrinterForm.value = true
}
function openPrinterEdit(p: Printer) {
  editingPrinter.value = p
  printerForm.fields.name = p.name
  printerForm.fields.print_agent_id = p.print_agent_id
  printerForm.fields.sizes = [...(p.capabilities?.sizes ?? [])]
  printerForm.fields.color = Boolean(p.capabilities?.color)
  printerForm.fields.is_active = p.is_active
  showPrinterForm.value = true
}
function toggleSize(size: string) {
  const s = printerForm.fields.sizes
  printerForm.fields.sizes = s.includes(size) ? s.filter((x) => x !== size) : [...s, size]
}

async function submitPrinter() {
  const payload = {
    name: printerForm.fields.name,
    print_agent_id: printerForm.fields.print_agent_id || null,
    capabilities: { sizes: printerForm.fields.sizes, color: printerForm.fields.color },
    is_active: printerForm.fields.is_active,
  }
  const ok = await printerForm.submit(async () =>
    editingPrinter.value
      ? updatePrinter(storeId, editingPrinter.value.id, payload)
      : createPrinter(storeId, payload),
  )
  if (ok) { showPrinterForm.value = false; toast.success('Printer saved.'); await refresh() }
}

async function confirmDeletePrinter() {
  if (!deletingPrinter.value) return
  try { await deletePrinter(storeId, deletingPrinter.value.id); toast.success('Printer removed.'); await refresh() }
  catch { toast.error('Could not remove printer.') }
  finally { deletingPrinter.value = null }
}

// ── Jobs ─────────────────────────────────────────────────────────────────────
const jobVariant: Record<string, 'neutral' | 'info' | 'warning' | 'success' | 'danger'> = {
  queued: 'neutral', sent: 'info', printing: 'warning', done: 'success', error: 'danger',
}
async function retry(job: PrintJob) {
  try { await retryJob(storeId, job.id); toast.success('Job re-queued.'); await refresh() }
  catch { toast.error('Could not retry job.') }
}

function agentName(id: string | null): string {
  return agents.value.find((a) => a.id === id)?.name ?? '—'
}
function lastSeen(a: PrintAgent): string {
  if (!a.last_seen_at) return 'never'
  return new Date(a.last_seen_at).toLocaleString()
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <AppPageHeader title="Printers" subtitle="Set up print agents and printers so paid orders print automatically.">
      <template #actions>
        <AppButton v-if="canManage" icon="plus" @click="openPrinterCreate"><span>Add printer</span></AppButton>
      </template>
    </AppPageHeader>

    <!-- Auto-print switch -->
    <AppCard class="mb-6">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Auto-print</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            When on, each paid order's files are queued to a matching printer automatically.
          </p>
        </div>
        <AppToggle :model-value="autoPrint" :disabled="!canManage" @update:model-value="toggleAutoPrint" />
      </div>
    </AppCard>

    <!-- Printers -->
    <AppCard title="Printers" class="mb-6">
      <div v-if="printers.length === 0" class="py-8 text-center text-sm text-gray-500">
        No printers yet. Add one and map it to an agent.
      </div>
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
        <div v-for="p in printers" :key="p.id" class="flex items-center justify-between gap-4 py-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <AppIcon name="printer" class="h-4 w-4 text-gray-400" />
              <span class="truncate font-medium text-gray-900 dark:text-gray-100">{{ p.name }}</span>
              <AppBadge :variant="p.is_active ? 'success' : 'neutral'">{{ p.is_active ? 'Active' : 'Off' }}</AppBadge>
            </div>
            <div class="mt-1 text-xs text-gray-500">
              {{ (p.capabilities?.sizes ?? []).join(', ') || 'Any size' }} ·
              {{ p.capabilities?.color ? 'Colour' : 'B&W' }} ·
              Agent: {{ agentName(p.print_agent_id) }}
            </div>
          </div>
          <AppRowActions v-if="canManage">
            <DropdownMenuItem @click="openPrinterEdit(p)">Edit</DropdownMenuItem>
            <DropdownMenuItem class="text-danger-600" @click="deletingPrinter = p">Delete</DropdownMenuItem>
          </AppRowActions>
        </div>
      </div>
    </AppCard>

    <!-- Agents -->
    <AppCard class="mb-6">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Print agents</h3>
        <AppButton v-if="canManage" size="sm" variant="secondary" icon="plus" @click="openAgentForm"><span>Add agent</span></AppButton>
      </div>
      <div v-if="agents.length === 0" class="py-6 text-center text-sm text-gray-500">
        No agents. An agent is the small program running on the shop PC that drives the printers.
      </div>
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
        <div v-for="a in agents" :key="a.id" class="flex items-center justify-between gap-4 py-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <span class="truncate font-medium text-gray-900 dark:text-gray-100">{{ a.name }}</span>
              <AppBadge :variant="a.is_active ? 'success' : 'neutral'">{{ a.is_active ? 'Active' : 'Off' }}</AppBadge>
            </div>
            <div class="mt-1 text-xs text-gray-500">
              {{ a.printers_count ?? 0 }} printer(s) · last seen {{ lastSeen(a) }}
            </div>
          </div>
          <AppRowActions v-if="canManage">
            <DropdownMenuItem @click="regenerate(a)">Regenerate token</DropdownMenuItem>
            <DropdownMenuItem class="text-danger-600" @click="deletingAgent = a">Delete</DropdownMenuItem>
          </AppRowActions>
        </div>
      </div>
    </AppCard>

    <!-- Jobs -->
    <AppCard title="Recent print jobs">
      <div v-if="jobs.length === 0" class="py-8 text-center text-sm text-gray-500">
        No print jobs yet. Paid orders will appear here when auto-print is on.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-left text-xs uppercase text-gray-400">
            <tr>
              <th class="py-2 pr-4">Order</th>
              <th class="py-2 pr-4">File</th>
              <th class="py-2 pr-4">Printer</th>
              <th class="py-2 pr-4">Copies</th>
              <th class="py-2 pr-4">Status</th>
              <th class="py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="j in jobs" :key="j.id">
              <td class="py-2 pr-4 font-mono text-xs">{{ j.order_code ?? '—' }}</td>
              <td class="py-2 pr-4 max-w-[200px] truncate">{{ j.file_name ?? '—' }}</td>
              <td class="py-2 pr-4">{{ j.printer_name ?? 'Unassigned' }}</td>
              <td class="py-2 pr-4">{{ j.copies }}</td>
              <td class="py-2 pr-4">
                <AppBadge :variant="jobVariant[j.status]">{{ j.status }}</AppBadge>
                <span v-if="j.error" class="ml-2 text-xs text-danger-500">{{ j.error }}</span>
              </td>
              <td class="py-2 text-right">
                <AppButton v-if="canManage && j.status === 'error'" size="sm" variant="secondary" @click="retry(j)"><span>Retry</span></AppButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <!-- Agent form -->
    <AppModal v-model="showAgentForm" title="New print agent">
      <div class="space-y-4">
        <AppInput v-model="agentForm.fields.name" label="Name" placeholder="Front counter PC" required :error="agentForm.getError('name')" />
        <p class="text-xs text-gray-500">A bearer token will be generated and shown once — install it into the local agent.</p>
      </div>
      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="showAgentForm = false"><span>Cancel</span></AppButton>
        <AppButton icon="plus" :loading="agentForm.loading" @click="submitAgent"><span>Create</span></AppButton>
      </template>
    </AppModal>

    <!-- Token reveal -->
    <AppModal :model-value="!!revealedToken" title="Agent token" @update:model-value="revealedToken = null">
      <div class="space-y-3">
        <p class="text-sm text-gray-600 dark:text-gray-300">Copy this token now — it will not be shown again.</p>
        <div class="flex items-center gap-2 rounded-md bg-gray-100 p-3 dark:bg-gray-800">
          <code class="flex-1 break-all text-xs">{{ revealedToken }}</code>
          <AppButton size="sm" variant="secondary" icon="clipboard" @click="copyToken"><span>Copy</span></AppButton>
        </div>
      </div>
      <template #footer>
        <AppButton icon="check" @click="revealedToken = null"><span>Done</span></AppButton>
      </template>
    </AppModal>

    <!-- Printer form -->
    <AppModal v-model="showPrinterForm" :title="editingPrinter ? 'Edit printer' : 'Add printer'">
      <div class="space-y-4">
        <AppInput v-model="printerForm.fields.name" label="Name" placeholder="HP LaserJet (front counter)" required :error="printerForm.getError('name')" />
        <AppSelect v-model="printerForm.fields.print_agent_id" label="Driven by agent" :options="agentOptions" />
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Paper sizes it can print</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in PAPER_SIZES" :key="size" type="button"
              class="rounded-full border px-3 py-1 text-sm"
              :class="printerForm.fields.sizes.includes(size)
                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-300'"
              @click="toggleSize(size)"
            >{{ size }}</button>
          </div>
          <p class="mt-1 text-xs text-gray-500">Leave all off to accept any size.</p>
        </div>
        <AppToggle v-model="printerForm.fields.color" label="Prints in colour" />
        <AppToggle v-model="printerForm.fields.is_active" label="Active" />
      </div>
      <template #footer>
        <AppButton variant="secondary" icon="x-mark" @click="showPrinterForm = false"><span>Cancel</span></AppButton>
        <AppButton :icon="editingPrinter ? 'check' : 'plus'" :loading="printerForm.loading" @click="submitPrinter">
          <span>{{ editingPrinter ? 'Save' : 'Add' }}</span>
        </AppButton>
      </template>
    </AppModal>

    <AppConfirmDialog
      :model-value="deletingPrinter !== null" danger title="Remove printer?"
      :message="`Remove ${deletingPrinter?.name}? Queued jobs will lose their assignment.`"
      confirm-label="Remove"
      @update:model-value="(v: boolean) => { if (!v) deletingPrinter = null }" @confirm="confirmDeletePrinter"
    />
    <AppConfirmDialog
      :model-value="deletingAgent !== null" danger title="Remove agent?"
      :message="`Remove ${deletingAgent?.name}? Its token stops working immediately.`"
      confirm-label="Remove"
      @update:model-value="(v: boolean) => { if (!v) deletingAgent = null }" @confirm="confirmDeleteAgent"
    />
  </div>
</template>
