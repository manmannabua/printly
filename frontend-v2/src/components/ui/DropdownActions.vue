<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

interface Props {
  /** Route to open in a new tab for CRUD management of this dropdown's source data. */
  manageRoute?: string
  /** Tooltip for the manage (external-link) icon. */
  manageTitle?: string
  /** Tooltip for the refresh icon. */
  refreshTitle?: string
  /** External loading state — if the consumer tracks loading centrally. */
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  manageRoute: undefined,
  manageTitle: 'Manage options',
  refreshTitle: 'Refresh options',
  loading: false,
})

const emit = defineEmits<{
  refresh: []
}>()

const internalBusy = ref(false)
const spinning = ref(false)

async function onRefresh(): Promise<void> {
  if (internalBusy.value || props.loading) return
  internalBusy.value = true
  spinning.value = true
  try {
    await Promise.resolve(emit('refresh'))
  } finally {
    internalBusy.value = false
    setTimeout(() => { spinning.value = false }, 400)
  }
}
</script>

<template>
  <span class="inline-flex items-center gap-1.5">
    <router-link
      v-if="manageRoute"
      :to="manageRoute"
      target="_blank"
      rel="noopener noreferrer"
      :title="manageTitle"
      class="text-gray-400 hover:text-primary-500 dark:text-gray-500 dark:hover:text-primary-400"
    >
      <AppIcon name="external-link" :size="14" />
    </router-link>
    <button
      type="button"
      :title="refreshTitle"
      :disabled="loading || internalBusy"
      class="text-gray-400 hover:text-primary-500 disabled:opacity-50 dark:text-gray-500 dark:hover:text-primary-400"
      @click="onRefresh"
    >
      <AppIcon
        name="refresh-cw"
        :size="14"
        :class="(loading || spinning) && 'animate-spin'"
      />
    </button>
  </span>
</template>
