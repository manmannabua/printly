<script setup lang="ts">
import { onMounted, ref } from 'vue'
import CareersLayout from './layouts/CareersLayout.vue'
import JobListClient from './pages/JobListClient.vue'
import JobDetailClient from './pages/JobDetailClient.vue'
import ApplicationPage from './pages/ApplicationPage.vue'
import ConfirmationPage from './pages/ConfirmationPage.vue'
import ContactPage from './pages/ContactPage.vue'
import CmsPageClient from './pages/CmsPageClient.vue'
import careersApi from './services/careersApi'
import type { PublicJobListing, PublicJobDetail, ApplyJobData, NavData, SiteSettingsData, CmsPageData } from './types/careers'
import type { ApplicationFormData } from './composables/useApplicationForm'

const appEl = document.getElementById('careers-app')
const page = appEl?.dataset.page ?? 'index'
const companyName = appEl?.dataset.company ?? ''
// Default true so missing/older Blade payloads keep current behavior.
const resumeUploadEnabled = appEl?.dataset.resumeUploadEnabled !== '0'
const editToken = appEl?.dataset.editToken ?? ''

// Parse server-provided data from Blade data-* attributes
let jobs: PublicJobListing[] = []
let job: PublicJobDetail | null = null
let applyJob: ApplyJobData | null = null
let departments: { id: string; name: string }[] = []
let employmentTypes: { id: string; name: string }[] = []
let navigation: NavData = { header: [], footer: [] }
let siteSettings: SiteSettingsData = {}
let cmsPage: CmsPageData | null = null

try {
  if (page === 'index' && appEl?.dataset.jobs) {
    jobs = JSON.parse(appEl.dataset.jobs)
  }
  if (page === 'index' && appEl?.dataset.departments) {
    departments = JSON.parse(appEl.dataset.departments)
  }
  if (page === 'index' && appEl?.dataset.employmentTypes) {
    employmentTypes = JSON.parse(appEl.dataset.employmentTypes)
  }
  if ((page === 'show' || page === 'apply') && appEl?.dataset.job) {
    const parsed = JSON.parse(appEl.dataset.job)
    if (page === 'show') job = parsed
    if (page === 'apply') applyJob = parsed
  }
  if (appEl?.dataset.navigation) {
    navigation = JSON.parse(appEl.dataset.navigation)
  }
  if (appEl?.dataset.siteSettings) {
    siteSettings = JSON.parse(appEl.dataset.siteSettings)
  }
  if (page === 'cms-page' && appEl?.dataset.cmsPage) {
    cmsPage = JSON.parse(appEl.dataset.cmsPage)
  }
} catch {
  // Fallback: Vue will fetch data from API if server data unavailable
}

const applicationSubmitted = ref(false)
const resumePdfToken = ref<string | null>(null)

function handleSubmitted(token?: string): void {
  applicationSubmitted.value = true
  resumePdfToken.value = token || null
}

// Edit-mode state. The edit page boot fetches the prefill payload before
// rendering ApplicationPage so the form can hydrate from a single source.
const editLoading = ref(page === 'edit')
const editError = ref<string | null>(null)
const editJob = ref<ApplyJobData | null>(null)
const editPrefill = ref<Partial<ApplicationFormData> | null>(null)

async function loadEditPrefill() {
  if (page !== 'edit' || !editToken) return
  try {
    const response = await careersApi.get(`/applicant-edit/${editToken}`)
    const data = response.data?.data?.applicant
    if (!data) {
      editError.value = 'Could not load your application. The link may be invalid.'
      return
    }
    editJob.value = data.job
      ? {
          id: data.job.id,
          title: data.job.title,
          slug: data.job.slug,
          location: data.job.location ?? null,
          department: data.job.department ?? null,
          closes_at: null,
        }
      : { id: '', title: 'your application', slug: '', location: null, department: null, closes_at: null }
    editPrefill.value = mapPrefillToForm(data.prefill ?? {})
  } catch (err: any) {
    if (err.response?.status === 410) {
      editError.value = err.response?.data?.message || 'This edit link is no longer valid.'
    } else {
      editError.value = 'Failed to load your application. Please try refreshing the page.'
    }
  } finally {
    editLoading.value = false
  }
}

function mapPrefillToForm(p: Record<string, any>): Partial<ApplicationFormData> {
  return {
    first_name: p.first_name ?? '',
    middle_name: p.middle_name ?? '',
    last_name: p.last_name ?? '',
    email: p.email ?? '',
    phone: p.phone ?? '',
    address: p.address ?? '',
    date_of_birth: p.date_of_birth ?? '',
    birth_place: p.birth_place ?? '',
    nationality: p.nationality ?? 'Filipino',
    gender: p.gender ?? '',
    civil_status: p.civil_status ?? '',
    father_name: p.father_name ?? '',
    mother_name: p.mother_name ?? '',
    expected_salary: p.expected_salary != null ? String(p.expected_salary) : '',
    cover_letter: p.cover_letter ?? '',
    government_ids: {
      tin: p.government_ids?.tin ?? '',
      sss: p.government_ids?.sss ?? '',
      philhealth: p.government_ids?.philhealth ?? '',
      pagibig: p.government_ids?.pagibig ?? '',
    },
    emergency_contact: {
      name: p.emergency_contact?.name ?? '',
      relationship: p.emergency_contact?.relationship ?? '',
      phone_primary: p.emergency_contact?.phone_primary ?? '',
    },
    educations: (p.educations ?? []).map((e: any) => ({
      institution: e.institution ?? '',
      level: e.level ?? '',
      status: e.status ?? '',
      degree: e.degree ?? '',
      field_of_study: e.field_of_study ?? '',
      start_date: e.start_date ?? '',
      end_date: e.end_date ?? '',
      is_current: !!e.is_current,
      description: e.description ?? '',
    })),
    work_experiences: (p.work_experiences ?? []).map((w: any) => ({
      company: w.company ?? '',
      company_address: w.company_address ?? '',
      job_title: w.job_title ?? '',
      start_date: w.start_date ?? '',
      end_date: w.end_date ?? '',
      is_current: !!w.is_current,
      description: w.description ?? '',
      reason_for_leaving: w.reason_for_leaving ?? '',
    })),
    skills: (p.skills ?? []).map((s: any) => ({
      name: s.name ?? '',
      category: s.category ?? 'other',
      proficiency_level: s.proficiency_level ?? 50,
      years_experience: s.years_experience != null ? String(s.years_experience) : '',
    })),
    references: (p.references ?? []).map((r: any) => ({
      name: r.name ?? '',
      relationship: r.relationship ?? '',
      company: r.company ?? '',
      position: r.position ?? '',
      email: r.email ?? '',
      phone: r.phone ?? '',
    })),
  }
}

onMounted(loadEditPrefill)
</script>

<template>
  <CareersLayout :company-name="companyName" :navigation="navigation" :site-settings="siteSettings">
    <JobListClient v-if="page === 'index'" :initial-jobs="jobs" :departments="departments" :employment-types="employmentTypes" :site-settings="siteSettings" />
    <JobDetailClient v-else-if="page === 'show' && job" :job="job" />
    <CmsPageClient v-else-if="page === 'cms-page' && cmsPage" :page-data="cmsPage" />
    <template v-else-if="page === 'apply' && applyJob">
      <ConfirmationPage v-if="applicationSubmitted" :job="applyJob" :resume-pdf-token="resumePdfToken" />
      <ApplicationPage v-else :job="applyJob" :resume-upload-enabled="resumeUploadEnabled" :company-name="companyName" @submitted="handleSubmitted" />
    </template>
    <template v-else-if="page === 'edit'">
      <div v-if="editLoading" class="mx-auto max-w-3xl px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        Loading your application…
      </div>
      <div v-else-if="editError" class="mx-auto max-w-2xl px-4 py-12">
        <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6 text-center">
          <h2 class="text-lg font-semibold text-red-800 dark:text-red-200">{{ editError }}</h2>
          <p class="mt-2 text-sm text-red-700 dark:text-red-300">Please contact our recruitment team for a new link.</p>
        </div>
      </div>
      <template v-else-if="editJob && editPrefill">
        <ConfirmationPage v-if="applicationSubmitted" :job="editJob" :resume-pdf-token="resumePdfToken" />
        <ApplicationPage
          v-else
          :job="editJob"
          :resume-upload-enabled="false"
          :company-name="companyName"
          :edit-token="editToken"
          :initial-form-data="editPrefill"
          @submitted="handleSubmitted"
        />
      </template>
    </template>
    <ContactPage v-else-if="page === 'contact'" :site-settings="siteSettings" />
    <ConfirmationPage v-else-if="page === 'confirmation'" />
  </CareersLayout>
</template>
