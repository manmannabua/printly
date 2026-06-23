<script setup lang="ts">
import { computed } from 'vue'
import { careersUrl } from '../utils/careersUrl'
import type { PublicJobDetail } from '../types/careers'

const props = defineProps<{
  job: PublicJobDetail
}>()

const isClosed = computed(() => {
  if (!props.job.closes_at) return false
  return new Date(props.job.closes_at) < new Date()
})

function formatSalary(min: string | null, max: string | null, currency: string | null): string {
  if (!min) return ''
  const fmt = (v: string) => Number(v).toLocaleString('en-PH')
  const cur = currency ?? 'PHP'
  if (max && max !== min) return `${cur} ${fmt(min)} – ${fmt(max)}`
  return `${cur} ${fmt(min)}`
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
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
  <div>
    <!-- Hero header -->
    <div class="bg-c-gradient py-12 sm:py-16">
      <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <a :href="careersUrl()" class="mb-4 inline-flex items-center text-sm text-c-primary-light transition-colors hover:text-white">
          <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          All Positions
        </a>

        <h1 class="text-2xl font-bold text-white sm:text-3xl lg:text-4xl">{{ job.title }}</h1>

        <!-- Quick info row in hero -->
        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-c-primary-light">
          <span v-if="job.location" class="flex items-center gap-1.5">
            <svg class="h-4 w-4 text-c-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
            </svg>
            {{ job.location }}
          </span>
          <span v-if="job.department" class="flex items-center gap-1.5">
            <svg class="h-4 w-4 text-c-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            {{ job.department.name }}
          </span>
          <span v-if="job.employment_type" class="flex items-center gap-1.5">
            <svg class="h-4 w-4 text-c-primary-light" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
            </svg>
            {{ job.employment_type.name }}
          </span>
        </div>

        <!-- Badges -->
        <div class="mt-4 flex flex-wrap gap-2">
          <span v-if="job.is_remote" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white backdrop-blur-sm">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Work from Home
          </span>
          <span v-if="job.show_salary && job.salary_range_min" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white backdrop-blur-sm">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ formatSalary(job.salary_range_min, job.salary_range_max, job.salary_currency) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Two-column layout -->
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="grid gap-8 lg:grid-cols-3">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:p-8">
            <section>
              <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-c-primary-light dark:bg-zinc-700">
                  <svg class="h-4.5 w-4.5 text-c-primary dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Description</h2>
              </div>
              <div class="rich-html-content max-w-none text-gray-700 dark:text-gray-300" v-html="job.description"></div>
            </section>
          </div>

          <!-- Requirements -->
          <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:p-8">
            <section>
              <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-zinc-700">
                  <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Requirements</h2>
              </div>
              <div class="rich-html-content max-w-none text-gray-700 dark:text-gray-300" v-html="job.requirements"></div>
            </section>
          </div>

          <!-- Benefits -->
          <div v-if="job.benefits" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 sm:p-8">
            <section>
              <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-zinc-700">
                  <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                  </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Benefits</h2>
              </div>
              <div class="rich-html-content max-w-none text-gray-700 dark:text-gray-300" v-html="job.benefits"></div>
            </section>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="sticky top-20 space-y-4">
            <!-- Apply CTA card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
              <template v-if="isClosed">
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Applications Closed</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">This position is no longer accepting applications.</p>
                <div class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-100 px-6 py-3 font-medium text-gray-400 cursor-not-allowed dark:bg-zinc-700 dark:text-gray-500">
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                  Position Closed
                </div>
              </template>
              <template v-else>
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Interested in this role?</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Submit your application and we'll get back to you.</p>
                <a
                  :href="careersUrl(`jobs/${job.slug}/apply`)"
                  class="flex w-full items-center justify-center gap-2 rounded-lg bg-c-primary px-6 py-3 font-medium text-white shadow-sm transition-all hover:bg-c-primary-hover hover:shadow-md"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                  </svg>
                  Apply Now
                </a>
              </template>
            </div>

            <!-- Job details card -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
              <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Job Details</h3>
              <div class="space-y-4">
                <!-- Location -->
                <div v-if="job.location" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Location</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ job.location }}</dd>
                  </div>
                </div>

                <!-- Remote -->
                <div v-if="job.is_remote" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Work Setup</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">Remote / Work from Home</dd>
                  </div>
                </div>

                <!-- Department -->
                <div v-if="job.department" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Department</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ job.department.name }}</dd>
                  </div>
                </div>

                <!-- Employment Type -->
                <div v-if="job.employment_type" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Employment Type</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ job.employment_type.name }}</dd>
                  </div>
                </div>

                <!-- Position -->
                <div v-if="job.position" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Position</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ job.position.title }}</dd>
                  </div>
                </div>

                <!-- Salary -->
                <div v-if="job.show_salary && job.salary_range_min" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Salary</dt>
                    <dd class="text-sm font-semibold text-gray-900 dark:text-white">
                      {{ formatSalary(job.salary_range_min, job.salary_range_max, job.salary_currency) }}
                    </dd>
                  </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100 dark:border-zinc-700"></div>

                <!-- Posted -->
                <div v-if="job.published_at" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Posted</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ timeAgo(job.published_at) }}</dd>
                  </div>
                </div>

                <!-- Deadline -->
                <div v-if="job.closes_at" class="flex items-start gap-3">
                  <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-zinc-700">
                    <svg class="h-4.5 w-4.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400 dark:text-gray-500">Application Deadline</dt>
                    <dd class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ formatDate(job.closes_at) }}</dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rich-html-content :deep(ul) {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}
.rich-html-content :deep(ol) {
  list-style-type: decimal;
  padding-left: 1.5rem;
  margin: 0.5rem 0;
}
.rich-html-content :deep(li) {
  margin: 0.125rem 0;
}
.rich-html-content :deep(li p) {
  margin: 0;
}
.rich-html-content :deep(p) {
  margin: 0.375rem 0;
}
.rich-html-content :deep(h2) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0.75rem 0 0.25rem;
}
.rich-html-content :deep(h3) {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0.5rem 0 0.25rem;
}
.rich-html-content :deep(blockquote) {
  border-left: 3px solid #d1d5db;
  padding-left: 0.75rem;
  color: #6b7280;
}
.rich-html-content :deep(a) {
  color: var(--c-primary, #4f46e5);
  text-decoration: underline;
}
</style>
