<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'

export interface ToastAction {
  label: string
  onClick: () => void
}

export interface Toast {
  id: number
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  icon?: string
  avatarName?: string
  avatarUrl?: string | null
  action?: ToastAction
}

export interface ToastOptions {
  action?: ToastAction
  icon?: string
  avatarName?: string
  avatarUrl?: string | null
}

const toasts = ref<Toast[]>([])
let nextId = 0

function add(
  type: Toast['type'],
  message: string,
  duration = 5000,
  options?: ToastOptions,
): void {
  const id = nextId++
  toasts.value.push({
    id,
    type,
    message,
    icon: options?.icon,
    avatarName: options?.avatarName,
    avatarUrl: options?.avatarUrl,
    action: options?.action,
  })
  if (duration > 0) {
    setTimeout(() => remove(id), duration)
  }
}

function remove(id: number): void {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

function handleAction(toast: Toast): void {
  toast.action?.onClick()
  remove(toast.id)
}

const iconMap: Record<Toast['type'], string> = {
  success: 'check-circle',
  error: 'x-circle',
  warning: 'exclamation-circle',
  info: 'info-circle',
}

const colorMap: Record<Toast['type'], string> = {
  success: 'border-green-400 bg-green-50 text-green-800 dark:border-green-600 dark:bg-green-900/30 dark:text-green-300',
  error: 'border-red-400 bg-red-50 text-red-800 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300',
  warning: 'border-yellow-400 bg-yellow-50 text-yellow-800 dark:border-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-300',
  info: 'border-blue-400 bg-blue-50 text-blue-800 dark:border-blue-600 dark:bg-blue-900/30 dark:text-blue-300',
}

defineExpose({ add, remove })
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed right-4 top-4 z-[9999] flex flex-col gap-2">
      <TransitionGroup
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex w-80 items-start gap-3 rounded-lg border-l-4 p-4 shadow-lg"
          :class="colorMap[toast.type]"
        >
          <AppAvatar
            v-if="toast.avatarName"
            :src="toast.avatarUrl ?? undefined"
            :first-name="toast.avatarName.split(' ')[0]"
            :last-name="toast.avatarName.split(' ').slice(1).join(' ') || undefined"
            size="xs"
            class="mt-0.5 shrink-0"
          />
          <AppIcon v-else :name="toast.icon ?? iconMap[toast.type]" :size="20" class="mt-0.5 shrink-0" />
          <p class="flex-1 text-sm">{{ toast.message }}</p>
          <button
            v-if="toast.action"
            class="shrink-0 rounded border border-current px-2 py-0.5 text-xs font-medium opacity-90 hover:opacity-100"
            @click="handleAction(toast)"
          >
            {{ toast.action.label }}
          </button>
          <button
            class="shrink-0 opacity-60 hover:opacity-100"
            @click="remove(toast.id)"
          >
            <AppIcon name="x-circle" :size="16" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
