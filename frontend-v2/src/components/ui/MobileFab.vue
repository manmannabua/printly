<script setup lang="ts">
import AppIcon from '@/components/common/AppIcon.vue'

defineProps<{
  icon: string
  label: string
}>()

defineEmits<{ click: [] }>()
</script>

<template>
  <button
    type="button"
    class="fixed bottom-[76px] right-4 z-30 flex items-center gap-2 rounded-full bg-primary px-5 py-3.5 text-sm font-semibold text-primary-foreground shadow-xl transition-transform active:scale-95"
    @click="$emit('click')"
  >
    <AppIcon :name="icon" :size="18" />
    {{ label }}
  </button>
</template>
