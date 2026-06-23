<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  modelValue: File | null
  error?: string
  isParsing?: boolean
  cardMode?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', file: File | null): void
}>()

const dragActive = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)

const ACCEPTED_TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
const MAX_SIZE_MB = 5

function handleFileSelect(event: Event): void {
  if (props.disabled) return
  const input = event.target as HTMLInputElement
  if (input.files?.length) {
    validateAndSet(input.files[0])
  }
}

function handleDrop(event: DragEvent): void {
  dragActive.value = false
  if (props.disabled) return
  if (event.dataTransfer?.files?.length) {
    validateAndSet(event.dataTransfer.files[0])
  }
}

function validateAndSet(file: File): void {
  if (!ACCEPTED_TYPES.includes(file.type)) {
    alert('Please upload a PDF, DOC, or DOCX file.')
    return
  }
  if (file.size > MAX_SIZE_MB * 1024 * 1024) {
    alert(`File size must be under ${MAX_SIZE_MB}MB.`)
    return
  }
  emit('update:modelValue', file)
}

function removeFile(): void {
  emit('update:modelValue', null)
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

function openFileDialog(): void {
  if (props.disabled) return
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
    <!-- Card mode: used in Step 1 side-by-side layout -->
    <template v-if="cardMode">
      <!-- Parsing state -->
      <div v-if="isParsing" class="rounded-xl border-2 border-dashed border-c-primary bg-c-primary/5 p-6 text-center">
        <svg class="mx-auto h-10 w-10 animate-spin text-c-primary" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Analyzing resume...</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Extracting your details</p>
      </div>

      <!-- File selected state -->
      <div v-else-if="modelValue" class="rounded-xl border-2 border-dashed border-c-primary bg-c-primary/5 p-6 text-center">
        <svg class="mx-auto h-10 w-10 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">{{ modelValue.name }}</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(modelValue.size) }}</p>
        <button
          type="button"
          class="mt-3 text-xs font-medium text-red-500 hover:text-red-700"
          @click="removeFile"
        >
          Remove
        </button>
      </div>

      <!-- Drop zone / click to upload -->
      <div
        v-else
        class="rounded-xl border-2 border-dashed p-6 text-center transition-colors"
        :class="disabled
          ? 'border-gray-200 dark:border-zinc-700 opacity-50 cursor-not-allowed'
          : ['border-gray-200 cursor-pointer hover:border-c-primary hover:bg-gray-50 dark:border-zinc-600 dark:hover:bg-zinc-800', dragActive ? 'border-c-primary bg-c-primary/5' : '']"
        @dragenter.prevent="!disabled && (dragActive = true)"
        @dragover.prevent="!disabled && (dragActive = true)"
        @dragleave.prevent="dragActive = false"
        @drop.prevent="handleDrop"
        @click="openFileDialog"
      >
        <svg v-if="disabled" class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <svg v-else class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Upload PDF Resume</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ disabled ? 'Verify your email first to upload a resume' : 'We\'ll extract your details automatically' }}
        </p>
      </div>

      <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </template>

    <!-- Default mode: used in other contexts -->
    <template v-else>
      <!-- File selected state -->
      <div v-if="modelValue" class="border rounded-lg p-4 dark:border-zinc-600 dark:bg-zinc-800" :class="isParsing ? 'border-c-primary bg-blue-50 dark:bg-zinc-800' : 'border-gray-200 bg-gray-50'">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <svg v-if="isParsing" class="animate-spin h-8 w-8 text-c-primary" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <svg v-else class="h-8 w-8 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ modelValue.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ isParsing ? 'Analyzing resume...' : formatFileSize(modelValue.size) }}
              </p>
            </div>
          </div>
          <button
            type="button"
            :disabled="isParsing"
            class="text-red-500 hover:text-red-700 text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed"
            @click="removeFile"
          >
            Remove
          </button>
        </div>
      </div>

      <!-- Drag-drop zone -->
      <div
        v-else
        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors dark:border-zinc-600"
        :class="disabled
          ? 'border-gray-300 opacity-50 cursor-not-allowed'
          : [dragActive ? 'border-c-primary bg-c-primary-light' : 'border-gray-300 hover:border-c-primary cursor-pointer', 'dark:hover:border-c-primary dark:hover:bg-zinc-800']"
        @dragenter.prevent="!disabled && (dragActive = true)"
        @dragover.prevent="!disabled && (dragActive = true)"
        @dragleave.prevent="dragActive = false"
        @drop.prevent="handleDrop"
        @click="openFileDialog"
      >
        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
          <span class="font-medium text-c-primary">Click to upload</span> or drag and drop
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, DOC, or DOCX (max {{ MAX_SIZE_MB }}MB)</p>
      </div>

      <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </template>

    <input
      ref="fileInputRef"
      type="file"
      accept=".pdf,.doc,.docx"
      class="hidden"
      @change="handleFileSelect"
    />
  </div>
</template>
