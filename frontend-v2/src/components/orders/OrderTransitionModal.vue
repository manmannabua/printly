<script setup lang="ts">
import { ref } from 'vue'
import { statusLabel } from '@/composables/useOrderBoard'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTextarea from '@/components/ui/AppTextarea.vue'

const props = defineProps<{
  modelValue: boolean
  orderCode: string
  fromStatus: string
  toStatus: string
}>()

const emit = defineEmits<{
  (e: 'confirm', reason?: string): void
  (e: 'cancel'): void
}>()

const reason = ref('')

// Statuses that benefit from a recorded reason (rejection / failure / cancel).
const NEEDS_REASON = ['rejected', 'failed', 'cancelled']

function confirm() {
  emit('confirm', reason.value.trim() || undefined)
  reason.value = ''
}

function cancel() {
  reason.value = ''
  emit('cancel')
}
</script>

<template>
  <AppModal
    :model-value="modelValue"
    title="Confirm status change"
    size="md"
    persistent
    @update:model-value="$event ? null : cancel()"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Move order <span class="font-mono font-semibold">{{ orderCode }}</span> from
        <span class="font-semibold">{{ statusLabel(fromStatus) }}</span> to
        <span class="font-semibold">{{ statusLabel(toStatus) }}</span>?
      </p>

      <AppTextarea
        v-model="reason"
        :label="NEEDS_REASON.includes(props.toStatus) ? 'Reason' : 'Note (optional)'"
        :rows="3"
        :placeholder="NEEDS_REASON.includes(props.toStatus) ? 'Why is this order being closed out?' : 'Add a note about this transition…'"
      />
    </div>

    <template #footer>
      <AppButton variant="secondary" icon="x-circle" @click="cancel">
        Cancel
      </AppButton>
      <AppButton icon="check" @click="confirm">
        Confirm
      </AppButton>
    </template>
  </AppModal>
</template>
