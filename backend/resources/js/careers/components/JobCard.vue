<script setup lang="ts">
import { careersUrl } from '../utils/careersUrl'
import type { PublicJobListing } from '../types/careers'

defineProps<{
  job: PublicJobListing
}>()

function formatSalary(min: string | null, max: string | null, currency: string | null): string {
  if (!min) return ''
  const fmt = (v: string) => Number(v).toLocaleString('en-PH')
  const cur = currency ?? 'PHP'
  if (max && max !== min) return `${cur} ${fmt(min)} – ${fmt(max)}`
  return `${cur} ${fmt(min)}`
}

function timeAgo(dateStr: string | null): string {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const days = Math.floor(diff / 86_400_000)
  if (days === 0) return 'Today'
  if (days === 1) return '1 day ago'
  if (days < 7) return `${days} days ago`
  if (days < 30) {
    const weeks = Math.floor(days / 7)
    return `${weeks} ${weeks === 1 ? 'week' : 'weeks'} ago`
  }
  const months = Math.floor(days / 30)
  return `${months} ${months === 1 ? 'month' : 'months'} ago`
}
</script>

<template>
  <a
    :href="careersUrl(`jobs/${job.slug}`)"
    class="group flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-c-light hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
  >
    <div class="flex flex-1 flex-col p-5">
      <!-- Header: Title + Badges -->
      <div>
        <h3 class="text-lg font-bold text-gray-900 transition-colors group-hover:text-c-primary dark:text-white">
          {{ job.title }}
        </h3>
        <div class="mt-2 flex flex-wrap gap-1.5">
          <span
            v-if="job.department"
            class="inline-flex items-center gap-1 rounded-full bg-c-primary-light px-2.5 py-0.5 text-xs font-medium text-c-primary-text dark:bg-zinc-700 dark:text-gray-300"
          >
            <!-- Building icon -->
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            {{ job.department.name }}
          </span>
          <span
            v-if="job.employment_type"
            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-zinc-700 dark:text-gray-300"
          >
            <!-- Briefcase icon -->
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ job.employment_type.name }}
          </span>
          <span
            v-if="job.is_remote"
            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-zinc-700 dark:text-gray-300"
          >
            <!-- Home/Wifi icon -->
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Work from Home
          </span>
        </div>
      </div>

      <!-- Detail rows with icons -->
      <div class="mt-4 space-y-2">
        <!-- Location -->
        <div v-if="job.location" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
          <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
          </svg>
          {{ job.location }}
        </div>

        <!-- Salary -->
        <div v-if="job.show_salary && job.salary_range_min" class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
          <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ formatSalary(job.salary_range_min, job.salary_range_max, job.salary_currency) }}
        </div>
      </div>

      <!-- Footer: Posted time + Deadline -->
      <div class="mt-auto pt-4">
        <div class="flex items-center justify-between border-t border-gray-100 pt-3 dark:border-zinc-700">
          <span v-if="job.published_at" class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <!-- Clock icon -->
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Posted {{ timeAgo(job.published_at) }}
          </span>
          <span v-if="job.closes_at" class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
            <!-- Calendar icon -->
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            <template v-if="new Date(job.closes_at) < new Date()">Closed</template>
            <template v-else>Closes {{ new Date(job.closes_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}</template>
          </span>
        </div>
      </div>
    </div>
  </a>
</template>
