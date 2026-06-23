<script setup lang="ts">
import { ref } from 'vue'
import { uploadFile, type UploadFolder } from '@/services/uploadService'
import { getErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import type { Upload } from '@/types/printly'
import AppButton from '@/components/ui/AppButton.vue'

const props = withDefaults(defineProps<{
  /** Folder the files are stored under. */
  folder: UploadFolder
  label?: string
  /** Max size per file, in MB. */
  maxSizeMb?: number
  disabled?: boolean
}>(), {
  maxSizeMb: 5,
})

const emit = defineEmits<{
  /** Fired once after all picked files have uploaded, with the successful results. */
  (e: 'uploaded', files: Upload[]): void
}>()

const toast = useToast()
const inputEl = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const progress = ref({ done: 0, total: 0 })

function pick(): void {
  inputEl.value?.click()
}

async function onChange(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (files.length === 0) return

  const valid = files.filter((f) => {
    if (!f.type.startsWith('image/')) {
      toast.error(`"${f.name}" is not an image and was skipped.`)
      return false
    }
    if (f.size > props.maxSizeMb * 1024 * 1024) {
      toast.error(`"${f.name}" is larger than ${props.maxSizeMb} MB and was skipped.`)
      return false
    }
    return true
  })

  if (valid.length === 0) {
    if (inputEl.value) inputEl.value.value = ''
    return
  }

  uploading.value = true
  progress.value = { done: 0, total: valid.length }
  const results: Upload[] = []
  try {
    for (const file of valid) {
      try {
        results.push(await uploadFile(file, props.folder))
      } catch (e: unknown) {
        toast.error(`${file.name}: ${getErrorMessage(e)}`)
      } finally {
        progress.value.done += 1
      }
    }
    if (results.length > 0) emit('uploaded', results)
  } finally {
    uploading.value = false
    if (inputEl.value) inputEl.value.value = ''
  }
}
</script>

<template>
  <div>
    <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>
    <input ref="inputEl" type="file" accept="image/*" multiple class="hidden" @change="onChange" />
    <AppButton
      size="sm"
      variant="secondary"
      icon="arrow-up-tray"
      :loading="uploading"
      :disabled="disabled"
      @click="pick"
    >
      <template v-if="uploading">Uploading {{ progress.done }}/{{ progress.total }}â€¦</template>
      <template v-else>Upload images</template>
    </AppButton>
    <p class="mt-1 text-xs text-gray-400">Select one or more â€” PNG, JPG or WebP up to {{ maxSizeMb }} MB each.</p>
  </div>
</template>
