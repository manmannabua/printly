<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Order, PrintJob, PrintJobStatus } from '@/types/printly'
import { statusLabel, STATUS_DOT } from '@/composables/useOrderBoard'
import { fileDownloadUrl } from '@/services/orderService'
import { retryJob } from '@/services/printerService'
import { useToast } from '@/composables/useToast'
import AppModal from '@/components/ui/AppModal.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const props = defineProps<{
  modelValue: boolean
  storeId: string
  order: Order | null
  loading?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [value: boolean]; 'changed': [] }>()

const toast = useToast()

const JOB_VARIANT: Record<PrintJobStatus, 'neutral' | 'info' | 'warning' | 'success' | 'danger'> = {
  queued: 'neutral', sent: 'info', printing: 'warning', done: 'success', error: 'danger',
}
const retrying = ref<string | null>(null)

async function retry(job: PrintJob) {
  retrying.value = job.id
  try { await retryJob(props.storeId, job.id); toast.success('Job re-queued.'); emit('changed') }
  catch { toast.error('Could not retry job.') }
  finally { retrying.value = null }
}

function formatMoney(cents: number): string {
  return `₱${(cents / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function fileMeta(file: { page_count: number | null, paper_size: string | null, is_color: boolean | null, size_bytes: number }): string {
  const parts: string[] = []
  if (file.page_count) parts.push(`${file.page_count}p`)
  if (file.paper_size) parts.push(file.paper_size)
  if (file.is_color !== null) parts.push(file.is_color ? 'Color' : 'B&W')
  parts.push(formatBytes(file.size_bytes))
  return parts.join(' · ')
}

function eventTime(iso: string | null | undefined): string {
  if (!iso) return ''
  return new Date(iso).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const allFiles = computed(() =>
  (props.order?.items ?? []).flatMap(i => i.files ?? []),
)
</script>

<template>
  <AppModal
    :model-value="modelValue"
    :title="order ? `Order ${order.code}` : 'Order'"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="flex justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="order" class="space-y-5">
      <!-- Status + totals -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
        <div class="flex items-center gap-2">
          <span class="h-2.5 w-2.5 rounded-full" :class="STATUS_DOT[order.status] ?? 'bg-gray-400'" />
          <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ statusLabel(order.status) }}</span>
          <span class="text-xs capitalize text-gray-400">· {{ order.payment_status.replace(/_/g, ' ') }}</span>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatMoney(order.total_cents) }}</p>
          <p v-if="order.fee_cents" class="text-xs text-gray-400">incl. {{ formatMoney(order.fee_cents) }} fee</p>
        </div>
      </div>

      <!-- Customer note -->
      <div v-if="order.notes" class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">
        <span class="font-medium">Note:</span> {{ order.notes }}
      </div>

      <!-- Items + print files -->
      <div class="space-y-4">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Items &amp; print files</h4>
        <div
          v-for="item in order.items ?? []"
          :key="item.id"
          class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
        >
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ item.product_name }} <span class="text-gray-400">×{{ item.quantity }}</span>
            </p>
            <span class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-300">{{ formatMoney(item.line_total_cents) }}</span>
          </div>

          <div v-if="item.files?.length" class="mt-2 space-y-1.5">
            <a
              v-for="file in item.files"
              :key="file.id"
              :href="fileDownloadUrl(storeId, file.id)"
              target="_blank"
              rel="noopener"
              class="flex items-center gap-2 rounded-md border border-gray-200 px-2.5 py-2 text-sm transition-colors hover:border-primary-400 hover:bg-primary-50 dark:border-gray-700 dark:hover:border-primary-500 dark:hover:bg-primary-900/20"
            >
              <AppIcon name="download" :size="16" class="shrink-0 text-primary-600 dark:text-primary-400" />
              <span class="min-w-0 flex-1 truncate text-gray-800 dark:text-gray-200">{{ file.original_name }}</span>
              <span class="shrink-0 text-xs text-gray-400">{{ fileMeta(file) }}</span>
            </a>
          </div>
          <p v-else class="mt-2 text-xs text-gray-400">No print files attached.</p>
        </div>
      </div>

      <!-- Download all hint -->
      <p v-if="allFiles.length > 1" class="text-xs text-gray-400">
        {{ allFiles.length }} files total. Each opens in a new tab for printing.
      </p>

      <!-- Auto-print jobs -->
      <div v-if="order.print_jobs?.length">
        <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Auto-print</h4>
        <div class="space-y-1.5">
          <div
            v-for="job in order.print_jobs"
            :key="job.id"
            class="flex items-center gap-2 rounded-md border border-gray-200 px-2.5 py-2 text-sm dark:border-gray-700"
          >
            <AppIcon name="printer" :size="16" class="shrink-0 text-gray-400" />
            <span class="min-w-0 flex-1 truncate text-gray-800 dark:text-gray-200">{{ job.file_name ?? 'File' }}</span>
            <span class="shrink-0 text-xs text-gray-400">{{ job.printer_name ?? 'Unassigned' }} · ×{{ job.copies }}</span>
            <AppBadge :variant="JOB_VARIANT[job.status]">{{ job.status }}</AppBadge>
            <AppButton
              v-if="job.status !== 'done' && job.status !== 'queued'"
              size="sm" variant="secondary" :loading="retrying === job.id" @click="retry(job)"
            ><span>Retry</span></AppButton>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div v-if="order.events?.length">
        <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Timeline</h4>
        <ol class="space-y-1.5">
          <li
            v-for="ev in order.events"
            :key="ev.id"
            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
          >
            <span class="h-1.5 w-1.5 shrink-0 rounded-full" :class="STATUS_DOT[ev.to_status] ?? 'bg-gray-400'" />
            <span class="font-medium text-gray-700 dark:text-gray-300">{{ statusLabel(ev.to_status) }}</span>
            <span class="capitalize">· {{ ev.actor_type }}</span>
            <span class="ml-auto">{{ eventTime(ev.created_at) }}</span>
          </li>
        </ol>
      </div>
    </div>
  </AppModal>
</template>
