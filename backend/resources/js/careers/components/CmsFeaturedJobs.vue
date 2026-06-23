<script setup lang="ts">
import { ref, onMounted } from 'vue'
import careersApi from '../services/careersApi'
import JobCard from './JobCard.vue'
import { careersUrl } from '../utils/careersUrl'
import type { PublicJobListing } from '../types/careers'

const props = withDefaults(
  defineProps<{
    data: {
      title?: string
      count?: number
    }
  }>(),
  {},
)

const jobs = ref<PublicJobListing[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const { data: response } = await careersApi.get('/jobs', {
      params: { per_page: props.data.count || 6 },
    })
    jobs.value = response.data || []
  } catch {
    // Silently fail — component gracefully shows empty state
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="py-8">
    <h2 v-if="data.title" class="mb-6 text-center text-2xl font-bold text-gray-900 sm:text-3xl">{{ data.title }}</h2>

    <div v-if="loading" class="flex justify-center py-8">
      <div class="h-8 w-8 animate-spin rounded-full border-4 spinner-c" />
    </div>

    <div v-else-if="jobs.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <JobCard v-for="job in jobs" :key="job.id" :job="job" />
    </div>

    <p v-else class="text-center text-sm text-gray-500">No open positions at this time.</p>

    <div v-if="!loading && jobs.length" class="mt-6 text-center">
      <a :href="careersUrl()" class="text-sm font-medium text-c-primary hover:text-c-primary-hover">
        View all positions &rarr;
      </a>
    </div>
  </section>
</template>
