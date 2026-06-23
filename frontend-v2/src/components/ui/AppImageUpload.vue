<script setup lang="ts">
import { ref, computed } from 'vue'
import { uploadFile, type UploadFolder } from '@/services/uploadService'
import { getErrorMessage } from '@/services/api'
import { useToast } from '@/composables/useToast'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const props = withDefaults(defineProps<{
  /** Stored media path (the value persisted on the entity). */
  modelValue: string | null
  /** Folder the file is stored under. */
  folder: UploadFolder
  /** Pre-existing image URL to preview (e.g. entity's *_url on edit). */
  previewUrl?: string | null
  label?: string
  /** 'square' for logos/covers, 'circle' for avatars. */
  shape?: 'square' | 'circle'
  /** Max file size in MB. */
  maxSizeMb?: number
  error?: string
}>(), {
  previewUrl: null,
  shape: 'square',
  maxSizeMb: 5,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const toast = useToast()
const inputEl = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const localUrl = ref<string | null>(null)

const displayUrl = computed(() => localUrl.value ?? props.previewUrl)

function pick(): void {
  inputEl.value?.click()
}

async function onChange(event: Event): Promise<void> {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.error('Please choose an image file.')
    return
  }
  if (file.size > props.maxSizeMb * 1024 * 1024) {
    toast.error(`Image must be ${props.maxSizeMb} MB or smaller.`)
    return
  }

  uploading.value = true
  try {
    const result = await uploadFile(file, props.folder)
    localUrl.value = result.url
    emit('update:modelValue', result.path)
  } catch (e: unknown) {
    toast.error(getErrorMessage(e))
  } finally {
    uploading.value = false
    if (inputEl.value) inputEl.value.value = ''
  }
}

function remove(): void {
  localUrl.value = null
  emit('update:modelValue', null)
}
</script>

<template>
  <div>
    <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>
    <div class="flex items-center gap-4">
      <div
        class="flex items-center justify-center overflow-hidden border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"
        :class="shape === 'circle' ? 'h-20 w-20 rounded-full' : 'h-20 w-28 rounded-lg'"
      >
        <AppSpinner v-if="uploading" />
        <img v-else-if="displayUrl" :src="displayUrl" alt="" class="h-full w-full object-cover" />
        <AppIcon v-else name="image" :size="22" class="text-gray-300 dark:text-gray-600" />
      </div>

      <div class="flex flex-col gap-2">
        <input ref="inputEl" type="file" accept="image/*" class="hidden" @change="onChange" />
        <div class="flex gap-2">
          <AppButton size="sm" variant="secondary" icon="arrow-up-tray" :loading="uploading" @click="pick">
            {{ displayUrl ? 'Replace' : 'Upload' }}
          </AppButton>
          <AppButton v-if="displayUrl" size="sm" variant="ghost" icon="trash" @click="remove">
            Remove
          </AppButton>
        </div>
        <p class="text-xs text-gray-400">PNG, JPG or WebP up to {{ maxSizeMb }} MB.</p>
      </div>
    </div>
    <p v-if="error" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
  </div>
</template>
