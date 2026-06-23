<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'

const props = defineProps<{
  modelValue: File | null
  error?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', file: File | null): void
}>()

const dragActive = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)

const ACCEPTED_TYPES = ['image/jpeg', 'image/png']
const MAX_SIZE_MB = 2

const previewUrl = computed(() => {
  if (props.modelValue) {
    return URL.createObjectURL(props.modelValue)
  }
  return null
})

// Clean up object URL on unmount
onBeforeUnmount(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
})

function handleFileSelect(event: Event): void {
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    validateAndSet(input.files[0])
  }
}

function handleDrop(event: DragEvent): void {
  dragActive.value = false
  if (event.dataTransfer?.files?.length) {
    validateAndSet(event.dataTransfer.files[0])
  }
}

function validateAndSet(file: File): void {
  if (!ACCEPTED_TYPES.includes(file.type)) {
    alert('Please upload a JPG or PNG image.')
    return
  }
  if (file.size > MAX_SIZE_MB * 1024 * 1024) {
    alert(`File size must be under ${MAX_SIZE_MB}MB.`)
    return
  }
  emit('update:modelValue', file)
}

function removeFile(): void {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
  }
  emit('update:modelValue', null)
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function openFileDialog(): void {
  fileInputRef.value?.click()
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
  <div>
    <!-- File selected state with preview -->
    <div v-if="modelValue" class="border border-gray-200 bg-gray-50 rounded-lg p-4 dark:border-zinc-600 dark:bg-zinc-800">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <img
            v-if="previewUrl"
            :src="previewUrl"
            alt="Photo preview"
            class="h-16 w-16 rounded-full object-cover border-2 border-gray-300 dark:border-zinc-500"
          />
          <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ modelValue.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(modelValue.size) }}</p>
          </div>
        </div>
        <button
          type="button"
          class="text-red-500 hover:text-red-700 text-sm font-medium"
          @click="removeFile"
        >
          Remove
        </button>
      </div>
    </div>

    <!-- Drag-drop zone -->
    <div
      v-else
      class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer dark:border-zinc-600 dark:hover:border-c-primary dark:hover:bg-zinc-800"
      :class="dragActive ? 'border-c-primary bg-c-primary-light' : 'border-gray-300 hover:border-c-primary'"
      @dragenter.prevent="dragActive = true"
      @dragover.prevent="dragActive = true"
      @dragleave.prevent="dragActive = false"
      @drop.prevent="handleDrop"
      @click="openFileDialog"
    >
      <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
        <span class="font-medium text-c-primary">Click to upload</span> or drag and drop
      </p>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">JPG or PNG (max {{ MAX_SIZE_MB }}MB)</p>
    </div>

    <input
      ref="fileInputRef"
      type="file"
      accept=".jpg,.jpeg,.png"
      class="hidden"
      @change="handleFileSelect"
    />

    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
