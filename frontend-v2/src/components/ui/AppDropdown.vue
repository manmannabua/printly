<script setup lang="ts">
import { ref, nextTick } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

export interface DropdownItem {
  key: string
  label: string
  icon?: string
  danger?: boolean
  disabled?: boolean
  divider?: boolean
}

withDefaults(defineProps<{
  items: DropdownItem[]
  align?: 'left' | 'right'
}>(), {
  align: 'right',
})

const emit = defineEmits<{
  select: [key: string]
}>()

const isOpen = ref(false)
const focusedIndex = ref(-1)
const menuRef = ref<HTMLElement | null>(null)

function toggle(): void {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    focusedIndex.value = -1
    nextTick(() => menuRef.value?.focus())
  }
}

function close(): void {
  isOpen.value = false
  focusedIndex.value = -1
}

function selectItem(item: DropdownItem): void {
  if (item.disabled || item.divider) return
  emit('select', item.key)
  close()
}

function getActionableItems(items: DropdownItem[]): DropdownItem[] {
  return items.filter(i => !i.divider && !i.disabled)
}

function handleKeydown(event: KeyboardEvent, items: DropdownItem[]): void {
  const actionable = getActionableItems(items)
  if (!actionable.length) return

  switch (event.key) {
    case 'ArrowDown': {
      event.preventDefault()
      focusedIndex.value = focusedIndex.value < actionable.length - 1 ? focusedIndex.value + 1 : 0
      break
    }
    case 'ArrowUp': {
      event.preventDefault()
      focusedIndex.value = focusedIndex.value > 0 ? focusedIndex.value - 1 : actionable.length - 1
      break
    }
    case 'Enter':
    case ' ': {
      event.preventDefault()
      const item = actionable[focusedIndex.value]
      if (item) {
        selectItem(item)
      }
      break
    }
    case 'Escape': {
      event.preventDefault()
      close()
      break
    }
  }
}

function isFocused(item: DropdownItem, items: DropdownItem[]): boolean {
  const actionable = getActionableItems(items)
  return actionable[focusedIndex.value] === item
}
</script>

<template>
  <div class="relative inline-block">
    <div @click="toggle">
      <slot name="trigger">
        <button
          type="button"
          class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
        >
          <AppIcon name="chevron-down" :size="20" />
        </button>
      </slot>
    </div>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        ref="menuRef"
        role="menu"
        tabindex="-1"
        class="absolute z-50 mt-2 min-w-[12rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg focus:outline-none dark:border-gray-600 dark:bg-gray-700"
        :class="align === 'right' ? 'right-0' : 'left-0'"
        @keydown="handleKeydown($event, items)"
      >
        <template v-for="item in items" :key="item.key">
          <div v-if="item.divider" class="my-1 border-t border-gray-200 dark:border-gray-600" />
          <button
            v-else
            type="button"
            role="menuitem"
            :disabled="item.disabled"
            :tabindex="-1"
            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm transition-colors"
            :class="[
              item.danger
                ? 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20'
                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600',
              item.disabled ? 'cursor-not-allowed opacity-50' : '',
              isFocused(item, items) ? (item.danger ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-100 dark:bg-gray-600') : '',
            ]"
            @click="selectItem(item)"
          >
            <AppIcon v-if="item.icon" :name="item.icon" :size="16" />
            {{ item.label }}
          </button>
        </template>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div
      v-if="isOpen"
      class="fixed inset-0 z-40"
      @click="close"
    />
  </div>
</template>
