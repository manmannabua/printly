<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import HeroSection from '../components/HeroSection.vue'
import CmsBlockRenderer from '../components/CmsBlockRenderer.vue'
import JobCard from '../components/JobCard.vue'
import JobFilters from '../components/JobFilters.vue'
import JobsPagination from '../components/JobsPagination.vue'
import type { PublicJobListing, SiteSettingsData } from '../types/careers'

const PAGE_SIZE = 12

const props = withDefaults(
  defineProps<{
    initialJobs: PublicJobListing[]
    departments: { id: string; name: string }[]
    employmentTypes: { id: string; name: string }[]
    siteSettings?: SiteSettingsData
  }>(),
  {
    siteSettings: () => ({}),
  },
)

const homepageSections = props.siteSettings?.homepage?.sections || []
// Split sections: hero goes above filters, others go below job list
const heroSection = homepageSections.find((s) => s.type === 'hero')
const otherSections = homepageSections.filter((s) => s.type !== 'hero')

const searchQuery = ref('')
const departmentFilter = ref<string | null>(null)
const employmentTypeFilter = ref<string | null>(null)
const currentPage = ref(1)

// Derive company name from parent data attribute
const companyName = document.getElementById('careers-app')?.dataset.company ?? ''

const filteredJobs = computed(() => {
  let result = props.initialJobs

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(
      (job) =>
        job.title.toLowerCase().includes(q) ||
        (job.location?.toLowerCase().includes(q) ?? false) ||
        (job.department?.name.toLowerCase().includes(q) ?? false),
    )
  }

  if (departmentFilter.value) {
    result = result.filter((job) => job.department?.id === departmentFilter.value)
  }

  if (employmentTypeFilter.value) {
    result = result.filter((job) => job.employment_type?.id === employmentTypeFilter.value)
  }

  return result
})

const hasActiveFilters = computed(
  () => !!searchQuery.value || departmentFilter.value !== null || employmentTypeFilter.value !== null,
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredJobs.value.length / PAGE_SIZE)),
)

const paginatedJobs = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filteredJobs.value.slice(start, start + PAGE_SIZE)
})

const pageRangeFrom = computed(() =>
  filteredJobs.value.length === 0 ? 0 : (currentPage.value - 1) * PAGE_SIZE + 1,
)
const pageRangeTo = computed(() =>
  Math.min(currentPage.value * PAGE_SIZE, filteredJobs.value.length),
)

// Reset to page 1 whenever filters change so the user lands on visible results.
watch([searchQuery, departmentFilter, employmentTypeFilter], () => {
  currentPage.value = 1
})

// Clamp page if the filtered list shrinks below the current page.
watch(totalPages, (total) => {
  if (currentPage.value > total) currentPage.value = total
})

function goToPage(page: number): void {
  currentPage.value = page
  // Smooth scroll the grid back into view so the user sees the new page.
  document.getElementById('careers-job-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<template>
  <div>
    <!-- CMS hero replaces default if available -->
    <CmsBlockRenderer v-if="heroSection" :block="heroSection" />
    <HeroSection
      v-else
      :company-name="companyName"
      :job-count="initialJobs.length"
      :hero-title="siteSettings?.branding?.hero_title"
      :hero-subtitle="siteSettings?.branding?.hero_subtitle"
      :hero-text-align="(siteSettings?.branding?.hero_text_align as 'center' | 'left' | 'right') ?? 'center'"
    />

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
      <JobFilters
        class="mb-8"
        :departments="departments"
        :employment-types="employmentTypes"
        @update:search="searchQuery = $event"
        @update:department="departmentFilter = $event"
        @update:employment-type="employmentTypeFilter = $event"
      />

      <!-- Results count -->
      <div v-if="filteredJobs.length > 0" class="mb-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
        <span v-if="hasActiveFilters">
          {{ filteredJobs.length }} {{ filteredJobs.length === 1 ? 'position' : 'positions' }} found
        </span>
        <span v-else></span>
        <span v-if="totalPages > 1">
          Showing {{ pageRangeFrom }}–{{ pageRangeTo }} of {{ filteredJobs.length }}
        </span>
      </div>

      <!-- Job grid + pagination -->
      <template v-if="filteredJobs.length > 0">
        <div
          id="careers-job-grid"
          class="grid scroll-mt-20 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <JobCard v-for="job in paginatedJobs" :key="job.id" :job="job" />
        </div>

        <JobsPagination
          :current-page="currentPage"
          :total-pages="totalPages"
          @update:page="goToPage"
        />
      </template>

      <!-- Empty state -->
      <div v-else class="rounded-xl border-2 border-dashed border-gray-200 py-16 text-center dark:border-zinc-700">
        <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
          {{ hasActiveFilters ? 'No positions match your criteria' : 'No open positions' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Check back soon for new opportunities!' }}
        </p>
      </div>

      <!-- CMS sections below job list -->
      <div v-if="otherSections.length" class="mt-12 space-y-8">
        <CmsBlockRenderer
          v-for="(section, idx) in otherSections"
          :key="'cms-' + idx"
          :block="section"
        />
      </div>
    </div>
  </div>
</template>
