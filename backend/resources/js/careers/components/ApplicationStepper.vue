<script setup lang="ts">
import { computed } from 'vue'
import type { ApplicationStep } from '../composables/useApplicationForm'

const props = defineProps<{
  currentStep: ApplicationStep
  totalSteps: number
  stepValid: Record<number, boolean>
}>()

defineEmits<{
  (e: 'go-to-step', step: ApplicationStep): void
}>()

const stepLabels: Record<number, string> = {
  1: 'Resume',
  2: 'Personal',
  3: 'Education',
  4: 'Experience',
  5: 'Skills',
  6: 'References',
  7: 'Photo',
  8: 'Review',
}

const stepIcons: Record<number, string> = {
  1: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
  2: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  3: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  4: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  5: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
  6: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
  7: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  8: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
}

const progressPercent = computed(() => {
  return Math.round(((props.currentStep - 1) / (props.totalSteps - 1)) * 100)
})
</script>

<template>
  <nav aria-label="Application steps" class="mb-8">
    <!-- Progress bar -->
    <div class="mb-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
      <span>Step {{ currentStep }} of {{ totalSteps }}</span>
      <span>{{ progressPercent }}% complete</span>
    </div>
    <div class="mb-6 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700">
      <div
        class="h-full rounded-full bg-c-gradient-r transition-all duration-300"
        :style="{ width: `${progressPercent}%` }"
      />
    </div>

    <!-- Step pills -->
    <div class="flex flex-wrap gap-1 pb-1 sm:gap-2">
      <button
        v-for="step in totalSteps"
        :key="step"
        type="button"
        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all sm:px-3 sm:py-2 sm:text-sm"
        :class="{
          'bg-c-primary text-white shadow-sm': step === currentStep,
          'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-600': step < currentStep,
          'bg-gray-100 text-gray-400 dark:bg-zinc-800 dark:text-gray-500': step > currentStep,
        }"
        :disabled="step > currentStep"
        @click="step <= currentStep && $emit('go-to-step', step as ApplicationStep)"
      >
        <!-- Icon -->
        <svg
          v-if="step < currentStep"
          class="h-4 w-4"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg
          v-else
          class="hidden h-4 w-4 sm:block"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stepIcons[step]" />
        </svg>
        <span>{{ stepLabels[step] }}</span>
      </button>
    </div>
  </nav>
</template>
