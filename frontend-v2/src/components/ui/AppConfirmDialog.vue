<script setup lang="ts">
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  message?: string
  confirmLabel?: string
  cancelLabel?: string
  confirmIcon?: string
  cancelIcon?: string
  danger?: boolean
  loading?: boolean
}>(), {
  title: 'Are you sure?',
  confirmLabel: 'Confirm',
  cancelLabel: 'Cancel',
  cancelIcon: 'x-mark',
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  confirm: []
  cancel: []
}>()

function close(): void {
  emit('update:modelValue', false)
}

function handleConfirm(): void {
  emit('confirm')
}

function handleCancel(): void {
  emit('cancel')
  close()
}
</script>

<template>
  <AppModal
    :model-value="modelValue"
    :title="title"
    size="sm"
    :closable="!loading"
    :persistent="loading"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="text-center" role="alertdialog" :aria-label="title">
      <div class="mb-4 flex justify-center">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-full"
          :class="danger ? 'bg-danger-100 dark:bg-danger-900/30' : 'bg-primary-100 dark:bg-primary-900/30'"
        >
          <AppIcon
            :name="danger ? 'exclamation-circle' : 'info-circle'"
            :size="24"
            :class="danger ? 'text-danger-600 dark:text-danger-400' : 'text-primary-600 dark:text-primary-400'"
          />
        </div>
      </div>
      <p v-if="message" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        {{ message }}
      </p>
      <slot />
    </div>

    <template #footer>
      <AppButton
        variant="secondary"
        :icon="cancelIcon"
        :disabled="loading"
        @click="handleCancel"
      >
        {{ cancelLabel }}
      </AppButton>
      <AppButton
        :variant="danger ? 'danger' : 'primary'"
        :icon="confirmIcon ?? (danger ? 'trash' : 'check')"
        :loading="loading"
        @click="handleConfirm"
      >
        {{ confirmLabel }}
      </AppButton>
    </template>
  </AppModal>
</template>
