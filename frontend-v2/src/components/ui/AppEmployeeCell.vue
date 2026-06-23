<script setup lang="ts">
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppCellLink from '@/components/ui/AppCellLink.vue'

defineProps<{
  firstName?: string
  lastName?: string
  fullName?: string
  employeeNumber?: string
  photoUrl?: string | null
  onClick?: () => void
}>()
</script>

<template>
  <div class="flex items-center gap-3">
    <AppAvatar :first-name="firstName" :last-name="lastName" :name="fullName" :src="photoUrl" size="sm" />
    <div>
      <AppCellLink v-if="onClick" medium @click="onClick">
        {{ fullName ?? '—' }}
      </AppCellLink>
      <p v-else class="font-medium text-gray-900 dark:text-gray-100">{{ fullName ?? '—' }}</p>
      <p class="text-xs text-gray-500 dark:text-gray-400">{{ employeeNumber }}</p>
    </div>
  </div>
</template>
