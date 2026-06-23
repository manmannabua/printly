<script setup lang="ts">
import { watch, onUnmounted } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  closable?: boolean
  persistent?: boolean
}>(), {
  size: 'md',
  closable: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const sizeClasses: Record<string, string> = {
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-[calc(100vw-2rem)]',
}

function close(): void {
  if (!props.closable) return
  emit('update:modelValue', false)
}

function handleBackdropClick(): void {
  if (props.persistent) return
  close()
}

function handleKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    close()
  }
}

watch(() => props.modelValue, (open) => {
  if (open) {
    document.addEventListener('keydown', handleKeydown)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        class="fixed inset-0 z-[9998] flex items-center justify-center p-4"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50"
          @click="handleBackdropClick"
        />

        <!-- Panel -->
        <div
          class="relative flex max-h-[calc(100vh-2rem)] w-full flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800"
          :class="sizeClasses[size]"
        >
          <!-- Header -->
          <div
            v-if="title || closable"
            class="flex items-center justify-between border-b border-gray-200 px-4 py-3 sm:px-6 sm:py-4 dark:border-gray-700"
          >
            <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
              {{ title }}
            </h3>
            <div v-else />
            <button
              v-if="closable"
              type="button"
              class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
              aria-label="Close"
              @click="close"
            >
              <AppIcon name="x-circle" :size="20" />
            </button>
          </div>

          <!-- Body -->
          <div class="modal-body flex-1 overflow-y-auto px-4 py-4 sm:px-6">
            <slot />
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 px-4 py-3 sm:px-6 sm:py-4 dark:border-gray-700"
          >
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Hide scrollbar visually but keep scroll behavior — applies to every AppModal. */
.modal-body {
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE / legacy Edge */
}
.modal-body::-webkit-scrollbar {
  display: none; /* Chromium / Safari */
}
</style>
