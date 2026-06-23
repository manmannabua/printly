<script setup lang="ts">
import { computed } from 'vue'
import { careersUrl } from '../utils/careersUrl'
import type { ApplyJobData } from '../types/careers'

const props = defineProps<{
  job?: ApplyJobData | null
  resumePdfToken?: string | null
}>()

const resumePdfUrl = computed(() =>
  props.resumePdfToken ? `/api/public/v1/resume-pdf/${props.resumePdfToken}` : null,
)
</script>

<template>
  <div class="max-w-xl mx-auto py-16 px-4 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 dark:bg-zinc-700">
      <svg class="w-8 h-8 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Application Submitted!</h1>
    <p class="text-gray-600 dark:text-gray-300 mb-2">
      Thank you for your interest<template v-if="job"> in <strong>{{ job.title }}</strong></template>.
    </p>
    <p class="text-gray-600 dark:text-gray-300 mb-8">
      We have received your application and will review it shortly. You'll hear from us soon.
    </p>
    <div class="flex items-center justify-center gap-3">
      <a
        v-if="resumePdfUrl"
        :href="resumePdfUrl"
        target="_blank"
        class="inline-flex items-center gap-2 rounded-lg border border-c-primary px-4 py-2 text-c-primary transition-colors hover:bg-c-primary-light"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Download Resume PDF
      </a>
      <a
        :href="careersUrl()"
        class="inline-flex items-center px-4 py-2 bg-c-primary text-white rounded-lg hover:bg-c-primary-hover transition-colors"
      >
        Browse More Positions
      </a>
    </div>
  </div>
</template>
