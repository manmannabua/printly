<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import careersApi from '../services/careersApi'
import ApplicationStepper from '../components/ApplicationStepper.vue'
import EntryFormSection from '../components/EntryFormSection.vue'
import EntryListPreview from '../components/EntryListPreview.vue'
import EducationEntryForm from '../components/EducationEntryForm.vue'
import WorkExperienceEntryForm from '../components/WorkExperienceEntryForm.vue'
import SkillEntryForm from '../components/SkillEntryForm.vue'
import ReferenceEntryForm from '../components/ReferenceEntryForm.vue'
import ReviewResumePreview from '../components/ReviewResumePreview.vue'
import ResumeUploader from '../components/ResumeUploader.vue'
import PhotoUploader from '../components/PhotoUploader.vue'
import HoneypotField from '../components/HoneypotField.vue'
import TermsAndConditionsModal from '../components/TermsAndConditionsModal.vue'
import {
  useApplicationForm,
  createEmptyEducation,
  createEmptyWorkExperience,
  createEmptySkill,
  createEmptyReference,
} from '../composables/useApplicationForm'
import type {
  ApplicationFormData,
  ApplicationStep,
  EducationEntry,
  WorkExperienceEntry,
  SkillEntry,
  ReferenceEntry,
} from '../composables/useApplicationForm'
import { careersUrl } from '../utils/careersUrl'
import type { ApplyJobData } from '../types/careers'

const props = withDefaults(
  defineProps<{
    job: ApplyJobData
    resumeUploadEnabled?: boolean
    companyName?: string
    /**
     * When set, the form runs in "edit existing application" mode:
     * starts at Step 2, hides OTP UI, posts to the edit endpoint.
     */
    editToken?: string
    /**
     * Hydrated form state from the edit-prefill payload. Ignored in apply mode.
     */
    initialFormData?: Record<string, unknown>
  }>(),
  {
    resumeUploadEnabled: true,
    companyName: 'the Company',
    editToken: undefined,
    initialFormData: () => ({}),
  },
)

const emit = defineEmits<{
  (e: 'submitted', resumePdfToken?: string): void
}>()

const {
  currentStep,
  form,
  submitting,
  submitError,
  serverErrors,
  totalSteps,
  stepValid,
  canProceed,
  hasMinimumContent,
  isParsing,
  resumeParsed,
  parseResume,
  nextStep,
  prevStep,
  goToStep,
  clearDraft,
  emailVerificationToken,
  emailVerified,
  setEmailVerified,
  editMode,
} = useApplicationForm(props.job, {
  editToken: props.editToken,
  initialFormData: props.initialFormData as Partial<ApplicationFormData>,
})

const parseBannerVisible = ref(true)

// Data Privacy Act consent — must be re-confirmed each session, not persisted to draft.
const termsAccepted = ref(false)
const termsModalOpen = ref(false)

// Step 1 gate: OTP email verification + terms agreement before the applicant starts the form.
// If the draft already placed them past Step 1 (page refresh mid-form), skip the gate —
// they'll re-verify via the Step 8 OTP panel before submitting.
const step1TermsAccepted = ref(currentStep.value > 1)
const step1ShowErrors = ref(false)

const canAdvanceStep1 = computed(
  () => currentStep.value > 1 || (step1TermsAccepted.value && emailVerified.value),
)

// Reset consent if the applicant changes their email after accepting,
// so a fresh confirmation is required against the address actually being submitted.
watch(() => form.value.email, () => {
  termsAccepted.value = false
})

function handleStep1Proceed(): void {
  if (!canAdvanceStep1.value) {
    step1ShowErrors.value = true
    return
  }
  nextStep()
}

async function handleResumeChange(file: File | null): Promise<void> {
  form.value.resume = file
  if (file) {
    parseBannerVisible.value = true
    await parseResume(file)
    if (resumeParsed.value) {
      if (canAdvanceStep1.value) {
        nextStep()
      } else {
        // Resume parsed and may have filled email — prompt user to confirm fields
        step1ShowErrors.value = true
      }
    }
  }
}

const isClosed = computed(() => {
  if (!props.job.closes_at) return false
  return new Date(props.job.closes_at) < new Date()
})

// Entry form/list state for each section
const editingEducation = ref<number | null>(null)
const editingExperience = ref<number | null>(null)
const editingSkill = ref<number | null>(null)
const editingReference = ref<number | null>(null)

const tempEducation = ref(createEmptyEducation())
const tempExperience = ref(createEmptyWorkExperience())
const tempSkill = ref(createEmptySkill())
const tempReference = ref(createEmptyReference())

// Education handlers
function addEducation() {
  if (!tempEducation.value.institution.trim()) return
  form.value.educations.push({ ...tempEducation.value })
  tempEducation.value = createEmptyEducation()
}

function editEducation(index: number) {
  editingEducation.value = index
  tempEducation.value = { ...form.value.educations[index] }
}

function saveEducation() {
  if (editingEducation.value === null) return
  form.value.educations[editingEducation.value] = { ...tempEducation.value }
  editingEducation.value = null
  tempEducation.value = createEmptyEducation()
}

function cancelEditEducation() {
  editingEducation.value = null
  tempEducation.value = createEmptyEducation()
}

function removeEducation(index: number) {
  form.value.educations.splice(index, 1)
  if (editingEducation.value === index) cancelEditEducation()
}

// Work Experience handlers
function addExperience() {
  if (!tempExperience.value.company.trim() || !tempExperience.value.job_title.trim()) return
  form.value.work_experiences.push({ ...tempExperience.value })
  tempExperience.value = createEmptyWorkExperience()
}

function editExperience(index: number) {
  editingExperience.value = index
  tempExperience.value = { ...form.value.work_experiences[index] }
}

function saveExperience() {
  if (editingExperience.value === null) return
  form.value.work_experiences[editingExperience.value] = { ...tempExperience.value }
  editingExperience.value = null
  tempExperience.value = createEmptyWorkExperience()
}

function cancelEditExperience() {
  editingExperience.value = null
  tempExperience.value = createEmptyWorkExperience()
}

function removeExperience(index: number) {
  form.value.work_experiences.splice(index, 1)
  if (editingExperience.value === index) cancelEditExperience()
}

// Skill handlers
function addSkill() {
  if (!tempSkill.value.name.trim()) return
  form.value.skills.push({ ...tempSkill.value })
  tempSkill.value = createEmptySkill()
}

function editSkillEntry(index: number) {
  editingSkill.value = index
  tempSkill.value = { ...form.value.skills[index] }
}

function saveSkill() {
  if (editingSkill.value === null) return
  form.value.skills[editingSkill.value] = { ...tempSkill.value }
  editingSkill.value = null
  tempSkill.value = createEmptySkill()
}

function cancelEditSkill() {
  editingSkill.value = null
  tempSkill.value = createEmptySkill()
}

function removeSkill(index: number) {
  form.value.skills.splice(index, 1)
  if (editingSkill.value === index) cancelEditSkill()
}

// Reference handlers
function addReference() {
  if (!tempReference.value.name.trim() || !tempReference.value.relationship.trim()) return
  form.value.references.push({ ...tempReference.value })
  tempReference.value = createEmptyReference()
}

function editReference(index: number) {
  editingReference.value = index
  tempReference.value = { ...form.value.references[index] }
}

function saveReference() {
  if (editingReference.value === null) return
  form.value.references[editingReference.value] = { ...tempReference.value }
  editingReference.value = null
  tempReference.value = createEmptyReference()
}

function cancelEditReference() {
  editingReference.value = null
  tempReference.value = createEmptyReference()
}

function removeReference(index: number) {
  form.value.references.splice(index, 1)
  if (editingReference.value === index) cancelEditReference()
}

function hasRichText(html: string): boolean {
  return !!html.replace(/<[^>]*>/g, '').trim()
}

const hasPendingEntry = computed(() => {
  const step = currentStep.value
  if (step === 3) {
    return (
      !!tempEducation.value.institution.trim() ||
      !!tempEducation.value.degree.trim() ||
      !!tempEducation.value.field_of_study.trim() ||
      !!tempEducation.value.start_date ||
      !!tempEducation.value.description.trim()
    )
  }
  if (step === 4) {
    return (
      !!tempExperience.value.company.trim() ||
      !!tempExperience.value.job_title.trim() ||
      !!tempExperience.value.start_date ||
      hasRichText(tempExperience.value.description)
    )
  }
  if (step === 5) {
    return !!tempSkill.value.name.trim()
  }
  if (step === 6) {
    return (
      !!tempReference.value.name.trim() ||
      !!tempReference.value.relationship.trim() ||
      !!tempReference.value.company.trim() ||
      !!tempReference.value.email.trim() ||
      !!tempReference.value.phone.trim()
    )
  }
  return false
})

// Summary renderers
function eduSummary(entry: EducationEntry): string {
  const parts = []
  if (entry.degree) parts.push(entry.degree)
  if (entry.field_of_study) parts.push(entry.field_of_study)
  const label = parts.join(' in ') || entry.institution
  const years = [
    entry.start_date ? new Date(entry.start_date).getFullYear() : '',
    entry.is_current ? 'Present' : entry.end_date ? new Date(entry.end_date).getFullYear() : '',
  ].filter(Boolean).join('–')
  return `${label} — ${entry.institution}${years ? `, ${years}` : ''}`
}

function expSummary(entry: WorkExperienceEntry): string {
  const years = [
    entry.start_date ? new Date(entry.start_date).getFullYear() : '',
    entry.is_current ? 'Present' : entry.end_date ? new Date(entry.end_date).getFullYear() : '',
  ].filter(Boolean).join('–')
  return `${entry.job_title} — ${entry.company}${years ? `, ${years}` : ''}`
}

// Names of fields missing on a committed work-experience entry, so the
// list preview can call out exactly which entry is blocking the step.
function expIncompleteFields(entry: WorkExperienceEntry): string[] {
  const missing: string[] = []
  if (!entry.company.trim()) missing.push('company')
  if (!entry.company_address.trim()) missing.push('company address')
  if (!entry.job_title.trim()) missing.push('job title')
  if (!entry.start_date.trim()) missing.push('start date')
  if (!entry.description.replace(/<[^>]*>/g, '').trim()) missing.push('description')
  if (!entry.is_current && !entry.reason_for_leaving.trim()) missing.push('reason for leaving')
  return missing
}

function skillSummary(entry: SkillEntry): string {
  const cat = entry.category.replace('_', ' ')
  return `${entry.name} (${entry.proficiency_level}%) — ${cat.charAt(0).toUpperCase() + cat.slice(1)}`
}

function refSummary(entry: ReferenceEntry): string {
  const parts = [entry.relationship]
  if (entry.company) parts.push(entry.company)
  return `${entry.name} — ${parts.join(', ')}`
}

// OTP flow
const otpSending = ref(false)
const otpSent = ref(false)
const otpCode = ref('')
const otpVerifying = ref(false)
const otpError = ref<string | null>(null)
const otpCooldown = ref(0)
let cooldownTimer: ReturnType<typeof setInterval> | null = null

// Reset OTP sent state if email changes
watch(() => form.value.email, () => {
  otpSent.value = false
  otpCode.value = ''
  otpError.value = null
})

function startCooldown(): void {
  otpCooldown.value = 180
  if (cooldownTimer) clearInterval(cooldownTimer)
  cooldownTimer = setInterval(() => {
    otpCooldown.value--
    if (otpCooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

async function sendOtp(): Promise<void> {
  if (otpSending.value || otpCooldown.value > 0) return
  otpError.value = null
  otpSending.value = true
  try {
    await careersApi.post('/otp/send', { email: form.value.email })
    otpSent.value = true
    startCooldown()
  } catch (err: any) {
    otpError.value = err.response?.data?.message || 'Failed to send code. Please try again.'
  } finally {
    otpSending.value = false
  }
}

async function verifyOtp(): Promise<void> {
  if (otpVerifying.value || otpCode.value.length !== 6) return
  otpError.value = null
  otpVerifying.value = true
  try {
    const response = await careersApi.post('/otp/verify', {
      email: form.value.email,
      code: otpCode.value,
    })
    setEmailVerified(response.data.token)
  } catch (err: any) {
    otpError.value = err.response?.data?.message || 'Invalid code. Please try again.'
  } finally {
    otpVerifying.value = false
  }
}

async function submitApplication(): Promise<void> {
  if (submitting.value) return
  submitError.value = null
  serverErrors.value = {}

  if (!hasMinimumContent.value) {
    submitError.value = 'Please complete all required sections before submitting.'
    return
  }

  if (!termsAccepted.value) {
    submitError.value = 'Please agree to the Terms and Conditions and Data Privacy Notice before submitting.'
    return
  }

  submitting.value = true

  try {
    const formData = new FormData()
    formData.append('first_name', form.value.first_name)
    formData.append('middle_name', form.value.middle_name)
    formData.append('last_name', form.value.last_name)
    formData.append('email', form.value.email)
    formData.append('phone', form.value.phone)
    formData.append('address', form.value.address)
    formData.append('date_of_birth', form.value.date_of_birth)
    formData.append('birth_place', form.value.birth_place)
    formData.append('nationality', form.value.nationality)
    formData.append('gender', form.value.gender)
    formData.append('civil_status', form.value.civil_status)
    formData.append('father_name', form.value.father_name)
    formData.append('mother_name', form.value.mother_name)
    formData.append('cover_letter', form.value.cover_letter)
    formData.append('email_verification_token', emailVerificationToken.value ?? '')
    formData.append('terms_accepted', '1')

    formData.append('government_ids[tin]', form.value.government_ids.tin)
    formData.append('government_ids[sss]', form.value.government_ids.sss)
    formData.append('government_ids[philhealth]', form.value.government_ids.philhealth)
    formData.append('government_ids[pagibig]', form.value.government_ids.pagibig)

    formData.append('emergency_contact[name]', form.value.emergency_contact.name)
    formData.append('emergency_contact[relationship]', form.value.emergency_contact.relationship)
    formData.append('emergency_contact[phone_primary]', form.value.emergency_contact.phone_primary)

    if (form.value.expected_salary) formData.append('expected_salary', form.value.expected_salary)
    if (form.value.website) formData.append('website', form.value.website)

    form.value.educations.forEach((edu, i) => {
      formData.append(`educations[${i}][institution]`, edu.institution)
      if (edu.level) formData.append(`educations[${i}][level]`, edu.level)
      if (edu.status) formData.append(`educations[${i}][status]`, edu.status)
      if (edu.degree) formData.append(`educations[${i}][degree]`, edu.degree)
      if (edu.field_of_study) formData.append(`educations[${i}][field_of_study]`, edu.field_of_study)
      if (edu.start_date) formData.append(`educations[${i}][start_date]`, edu.start_date)
      if (edu.end_date) formData.append(`educations[${i}][end_date]`, edu.end_date)
      if (edu.is_current) formData.append(`educations[${i}][is_current]`, '1')
      if (edu.description) formData.append(`educations[${i}][description]`, edu.description)
    })

    form.value.work_experiences.forEach((exp, i) => {
      formData.append(`work_experiences[${i}][company]`, exp.company)
      if (exp.company_address) formData.append(`work_experiences[${i}][company_address]`, exp.company_address)
      formData.append(`work_experiences[${i}][job_title]`, exp.job_title)
      if (exp.start_date) formData.append(`work_experiences[${i}][start_date]`, exp.start_date)
      if (exp.end_date) formData.append(`work_experiences[${i}][end_date]`, exp.end_date)
      if (exp.is_current) formData.append(`work_experiences[${i}][is_current]`, '1')
      if (exp.description) formData.append(`work_experiences[${i}][description]`, exp.description)
      if (exp.reason_for_leaving) formData.append(`work_experiences[${i}][reason_for_leaving]`, exp.reason_for_leaving)
    })

    form.value.skills.forEach((skill, i) => {
      formData.append(`skills[${i}][name]`, skill.name)
      formData.append(`skills[${i}][proficiency_level]`, String(skill.proficiency_level))
      if (skill.category) formData.append(`skills[${i}][category]`, skill.category)
      if (skill.years_experience) formData.append(`skills[${i}][years_experience]`, skill.years_experience)
    })

    form.value.references.forEach((ref, i) => {
      formData.append(`references[${i}][name]`, ref.name)
      formData.append(`references[${i}][relationship]`, ref.relationship)
      if (ref.company) formData.append(`references[${i}][company]`, ref.company)
      if (ref.position) formData.append(`references[${i}][position]`, ref.position)
      if (ref.email) formData.append(`references[${i}][email]`, ref.email)
      if (ref.phone) formData.append(`references[${i}][phone]`, ref.phone)
    })

    if (form.value.resume) {
      formData.append('resume', form.value.resume)
    }

    if (form.value.photo) {
      formData.append('photo', form.value.photo)
    }

    const endpoint = editMode
      ? `/applicant-edit/${props.editToken}`
      : `/jobs/${props.job.slug}/apply`
    const response = await careersApi.post(endpoint, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    clearDraft()
    emit('submitted', response.data?.resume_pdf_token)
  } catch (err: any) {
    if (err.response?.status === 422) {
      const data = err.response.data
      serverErrors.value = data.errors || {}
      submitError.value = data.message || 'Please fix the errors below.'
    } else if (err.response?.status === 409) {
      submitError.value = 'You have already applied for this position.'
    } else if (err.response?.status === 410) {
      submitError.value = err.response?.data?.message || 'This edit link is no longer valid. Please request a new one from the recruitment team.'
    } else {
      submitError.value = 'An unexpected error occurred. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Job header -->
    <div class="mb-6">
      <a v-if="!editMode" :href="careersUrl(`jobs/${job.slug}`)" class="text-sm text-c-primary hover:text-c-primary-hover mb-2 inline-block">&larr; Back to job details</a>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        {{ editMode ? 'Update your application' : `Apply for ${job.title}` }}
        <span v-if="editMode && job.title" class="block text-base font-normal text-gray-500 dark:text-gray-400 mt-1">for {{ job.title }}</span>
      </h1>
      <p v-if="job.department && !editMode" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ job.department.name }}</p>
    </div>

    <!-- Edit mode banner -->
    <div v-if="editMode" class="mb-6 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 p-4">
      <div class="flex items-start gap-3">
        <svg class="mt-0.5 h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="text-sm text-blue-900 dark:text-blue-200">
          <p class="font-medium">You're updating your existing application.</p>
          <p class="mt-1">Your current details have been pre-filled. Edit anything that needs to change and submit — your changes will be reviewed by the recruitment team before they go live.</p>
        </div>
      </div>
    </div>

    <!-- Closed notice -->
    <div v-if="isClosed" class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:bg-zinc-800 dark:border-zinc-700">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-zinc-700">
        <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Applications Closed</h2>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This position is no longer accepting applications.</p>
      <a :href="careersUrl(`jobs/${job.slug}`)" class="mt-6 inline-block text-sm font-medium text-c-primary hover:text-c-primary-hover">&larr; Back to job details</a>
    </div>

    <!-- Stepper -->
    <ApplicationStepper
      v-if="!isClosed"
      :current-step="currentStep"
      :total-steps="totalSteps"
      :step-valid="stepValid"
      @go-to-step="goToStep"
    />

    <!-- Parse success banner -->
    <div
      v-if="resumeParsed && parseBannerVisible"
      class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start justify-between gap-4"
    >
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-blue-800">We filled in your details from your resume — review and edit as needed.</p>
      </div>
      <button type="button" class="text-blue-400 hover:text-blue-600 flex-shrink-0" @click="parseBannerVisible = false">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Error banner -->
    <div v-if="!isClosed && submitError" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-sm font-medium text-red-800">{{ submitError }}</p>
      <ul v-if="Object.keys(serverErrors).length" class="mt-2 space-y-0.5 list-disc list-inside">
        <li v-for="(messages, field) in serverErrors" :key="field" class="text-sm text-red-700">
          {{ messages[0] }}
        </li>
      </ul>
    </div>

    <form v-if="!isClosed" @submit.prevent="currentStep === 8 ? submitApplication() : nextStep()">
      <HoneypotField v-model="form.website" />

      <!-- Step 1: Resume Upload (hidden in edit mode — wizard starts at step 2) -->
      <div v-show="currentStep === 1 && !editMode" class="space-y-6">
        <div class="text-center">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ resumeUploadEnabled ? 'How would you like to start?' : 'Ready to apply?' }}
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ resumeUploadEnabled
              ? 'Upload a PDF resume to auto-fill your application, or create your application from scratch.'
              : 'Click below to fill in your application details.' }}
          </p>
        </div>

        <!-- Step 1 gate: OTP email verification + terms agreement (hidden if resuming a saved draft) -->
        <div v-if="currentStep === 1" class="rounded-xl border border-gray-200 bg-white dark:bg-zinc-800 dark:border-zinc-700 p-5 space-y-4">

          <!-- Email input -->
          <div>
            <label for="step1_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Email Address <span class="text-red-500">*</span>
            </label>
            <input
              id="step1_email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              :disabled="emailVerified"
              placeholder="you@example.com"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white disabled:opacity-60 disabled:cursor-not-allowed"
            />
          </div>

          <!-- OTP verified state -->
          <div v-if="emailVerified" class="flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-800">
              <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-green-800 dark:text-green-300">Email verified</p>
              <p class="text-xs text-green-700 dark:text-green-400">{{ form.email }}</p>
            </div>
            <button
              type="button"
              class="text-xs text-green-600 dark:text-green-400 underline hover:no-underline"
              @click="clearEmailVerification(); otpSent = false; otpCode = ''; otpError = null"
            >
              Change
            </button>
          </div>

          <!-- OTP unverified state -->
          <div v-else class="space-y-3">
            <div v-if="otpError" class="rounded-md bg-red-50 border border-red-200 px-3 py-2 dark:bg-red-900/20 dark:border-red-800">
              <p class="text-sm text-red-700 dark:text-red-400">{{ otpError }}</p>
            </div>

            <!-- Send code -->
            <div v-if="!otpSent" class="flex items-center gap-3">
              <button
                type="button"
                :disabled="otpSending || !form.email.trim()"
                class="inline-flex items-center gap-2 rounded-lg bg-c-primary px-4 py-2 text-sm font-medium text-white hover:bg-c-primary-hover disabled:opacity-50 disabled:cursor-not-allowed"
                @click="sendOtp"
              >
                <svg v-if="otpSending" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ otpSending ? 'Sending…' : 'Send Verification Code' }}
              </button>
              <p class="text-xs text-gray-500 dark:text-gray-400">We'll send a 6-digit code to confirm your email is active.</p>
            </div>

            <!-- Enter code -->
            <div v-else class="space-y-3 text-center">
              <p class="text-sm text-gray-600 dark:text-gray-300">
                A verification code was sent to <strong>{{ form.email }}</strong>. Enter it below.
              </p>
              <div class="flex items-center justify-center gap-3">
                <input
                  v-model="otpCode"
                  type="text"
                  inputmode="numeric"
                  maxlength="6"
                  placeholder="000000"
                  class="w-36 rounded-md border-gray-300 text-center text-xl font-mono tracking-widest shadow-sm focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                />
                <button
                  type="button"
                  :disabled="otpVerifying || otpCode.length !== 6"
                  class="inline-flex items-center gap-2 rounded-lg bg-c-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-c-primary-hover disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="verifyOtp"
                >
                  <svg v-if="otpVerifying" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                  {{ otpVerifying ? 'Verifying…' : 'Verify' }}
                </button>
              </div>
              <!-- Resend timer -->
              <div v-if="otpCooldown > 0" class="mx-auto max-w-sm rounded-md bg-gray-50 dark:bg-zinc-700/50 border border-gray-200 dark:border-zinc-600 px-3 py-2">
                <div class="flex items-center justify-between mb-1.5">
                  <div class="flex items-center gap-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Resend available in
                  </div>
                  <span class="text-sm font-semibold tabular-nums text-c-primary">{{ otpCooldown }}s</span>
                </div>
                <div class="h-1 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-600">
                  <div
                    class="h-full rounded-full bg-c-gradient-r transition-all duration-1000 ease-linear"
                    :style="{ width: `${(otpCooldown / 180) * 100}%` }"
                  />
                </div>
              </div>
              <button v-else type="button" class="text-xs text-c-primary hover:text-c-primary-hover underline hover:no-underline" @click="sendOtp">
                Resend code
              </button>
            </div>
          </div>

          <!-- T&C checkbox — shown once email is verified -->
          <div v-if="emailVerified" class="border-t border-gray-100 dark:border-zinc-700 pt-4">
            <label class="flex cursor-pointer items-start gap-3">
              <input
                v-model="step1TermsAccepted"
                type="checkbox"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-c-primary focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-900"
              />
              <span class="text-sm text-gray-700 dark:text-gray-300">
                I have read and agree to the
                <button
                  type="button"
                  class="font-medium text-c-primary hover:underline"
                  @click.prevent="termsModalOpen = true"
                >
                  Terms and Conditions and Data Privacy Notice
                </button>.
                I consent to the collection and processing of my personal information for recruitment purposes.
                <span class="text-red-500">*</span>
              </span>
            </label>
          </div>

          <p v-if="step1ShowErrors && !canAdvanceStep1" class="text-sm font-medium text-red-600 dark:text-red-400">
            Please verify your email and agree to the Terms and Conditions before continuing.
          </p>
        </div>

        <div
          :class="resumeUploadEnabled
            ? 'grid grid-cols-1 sm:grid-cols-2 gap-4'
            : 'flex justify-center'"
        >
          <!-- Option: Upload PDF -->
          <ResumeUploader
            v-if="resumeUploadEnabled"
            :model-value="form.resume"
            :error="serverErrors.resume?.[0]"
            :is-parsing="isParsing"
            :disabled="!canAdvanceStep1"
            card-mode
            @update:model-value="handleResumeChange"
          />

          <!-- Option: Create manually -->
          <button
            type="button"
            :disabled="isParsing || !canAdvanceStep1"
            :class="[
              'rounded-xl border-2 border-dashed border-gray-200 p-6 text-center transition-colors hover:border-c-primary hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:border-gray-200 disabled:hover:bg-transparent dark:border-zinc-600 dark:hover:bg-zinc-800 dark:disabled:hover:bg-transparent dark:disabled:hover:border-zinc-600',
              resumeUploadEnabled ? '' : 'w-full max-w-sm',
            ]"
            @click="handleStep1Proceed"
          >
            <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
              {{ resumeUploadEnabled ? 'Create from Scratch' : 'Start Application' }}
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fill in your details manually</p>
          </button>
        </div>
      </div>

      <!-- Step 2: Personal Info -->
      <div v-show="currentStep === 2" class="space-y-4">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Personal Information</h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All fields marked with <span class="text-red-500">*</span> are required.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              First Name <span class="text-red-500">*</span>
            </label>
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.first_name" class="mt-1 text-sm text-red-600">{{ serverErrors.first_name[0] }}</p>
          </div>
          <div>
            <label for="middle_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Middle Name
            </label>
            <input
              id="middle_name"
              v-model="form.middle_name"
              type="text"
              maxlength="100"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.middle_name" class="mt-1 text-sm text-red-600">{{ serverErrors.middle_name[0] }}</p>
          </div>
          <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Last Name <span class="text-red-500">*</span>
            </label>
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.last_name" class="mt-1 text-sm text-red-600">{{ serverErrors.last_name[0] }}</p>
          </div>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Email Address <span class="text-red-500">*</span>
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
          />
          <p v-if="serverErrors.email" class="mt-1 text-sm text-red-600">{{ serverErrors.email[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Phone Number <span class="text-red-500">*</span>
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
              placeholder="+63-917-123-4567"
            />
            <p v-if="serverErrors.phone" class="mt-1 text-sm text-red-600">{{ serverErrors.phone[0] }}</p>
          </div>
          <div>
            <label for="expected_salary" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Expected Salary</label>
            <input
              id="expected_salary"
              v-model="form.expected_salary"
              type="number"
              min="0"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
              placeholder="Monthly salary expectation"
            />
          </div>
        </div>

        <div>
          <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Current Address <span class="text-red-500">*</span>
          </label>
          <input
            id="address"
            v-model="form.address"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
            placeholder="e.g. 123 Main St, Quezon City, Metro Manila"
          />
          <p v-if="serverErrors.address" class="mt-1 text-sm text-red-600">{{ serverErrors.address[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label for="date_of_birth" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Date of Birth <span class="text-red-500">*</span>
            </label>
            <input
              id="date_of_birth"
              v-model="form.date_of_birth"
              type="date"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.date_of_birth" class="mt-1 text-sm text-red-600">{{ serverErrors.date_of_birth[0] }}</p>
          </div>
          <div>
            <label for="birth_place" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Birth Place <span class="text-red-500">*</span>
            </label>
            <input
              id="birth_place"
              v-model="form.birth_place"
              type="text"
              maxlength="200"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
              placeholder="e.g. Quezon City"
            />
            <p v-if="serverErrors.birth_place" class="mt-1 text-sm text-red-600">{{ serverErrors.birth_place[0] }}</p>
          </div>
          <div>
            <label for="nationality" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nationality <span class="text-red-500">*</span>
            </label>
            <input
              id="nationality"
              v-model="form.nationality"
              type="text"
              maxlength="100"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.nationality" class="mt-1 text-sm text-red-600">{{ serverErrors.nationality[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Gender <span class="text-red-500">*</span>
            </label>
            <select
              id="gender"
              v-model="form.gender"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
              <option value="">— Select —</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
            <p v-if="serverErrors.gender" class="mt-1 text-sm text-red-600">{{ serverErrors.gender[0] }}</p>
          </div>
          <div>
            <label for="civil_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Civil Status <span class="text-red-500">*</span>
            </label>
            <select
              id="civil_status"
              v-model="form.civil_status"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            >
              <option value="">— Select —</option>
              <option value="single">Single</option>
              <option value="married">Married</option>
              <option value="divorced">Divorced</option>
              <option value="widowed">Widowed</option>
            </select>
            <p v-if="serverErrors.civil_status" class="mt-1 text-sm text-red-600">{{ serverErrors.civil_status[0] }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="father_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Father's Name <span class="text-red-500">*</span>
            </label>
            <input
              id="father_name"
              v-model="form.father_name"
              type="text"
              maxlength="200"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.father_name" class="mt-1 text-sm text-red-600">{{ serverErrors.father_name[0] }}</p>
          </div>
          <div>
            <label for="mother_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Mother's Name <span class="text-red-500">*</span>
            </label>
            <input
              id="mother_name"
              v-model="form.mother_name"
              type="text"
              maxlength="200"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
            />
            <p v-if="serverErrors.mother_name" class="mt-1 text-sm text-red-600">{{ serverErrors.mother_name[0] }}</p>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Government IDs</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Optional — provide what you have on hand.</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="gov_tin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">TIN</label>
              <input
                id="gov_tin"
                v-model="form.government_ids.tin"
                type="text"
                maxlength="50"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="123-456-789-000"
              />
              <p v-if="serverErrors['government_ids.tin']" class="mt-1 text-sm text-red-600">{{ serverErrors['government_ids.tin'][0] }}</p>
            </div>
            <div>
              <label for="gov_sss" class="block text-sm font-medium text-gray-700 dark:text-gray-300">SSS</label>
              <input
                id="gov_sss"
                v-model="form.government_ids.sss"
                type="text"
                maxlength="50"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="11-2345678-9"
              />
              <p v-if="serverErrors['government_ids.sss']" class="mt-1 text-sm text-red-600">{{ serverErrors['government_ids.sss'][0] }}</p>
            </div>
            <div>
              <label for="gov_philhealth" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PhilHealth</label>
              <input
                id="gov_philhealth"
                v-model="form.government_ids.philhealth"
                type="text"
                maxlength="50"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="12-345678901-2"
              />
              <p v-if="serverErrors['government_ids.philhealth']" class="mt-1 text-sm text-red-600">{{ serverErrors['government_ids.philhealth'][0] }}</p>
            </div>
            <div>
              <label for="gov_pagibig" class="block text-sm font-medium text-gray-700 dark:text-gray-300">HDMF (Pag-IBIG)</label>
              <input
                id="gov_pagibig"
                v-model="form.government_ids.pagibig"
                type="text"
                maxlength="50"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="1234-5678-9012"
              />
              <p v-if="serverErrors['government_ids.pagibig']" class="mt-1 text-sm text-red-600">{{ serverErrors['government_ids.pagibig'][0] }}</p>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Emergency Contact</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label for="ec_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Contact Name <span class="text-red-500">*</span>
              </label>
              <input
                id="ec_name"
                v-model="form.emergency_contact.name"
                type="text"
                maxlength="200"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
              />
              <p v-if="serverErrors['emergency_contact.name']" class="mt-1 text-sm text-red-600">{{ serverErrors['emergency_contact.name'][0] }}</p>
            </div>
            <div>
              <label for="ec_relationship" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Relationship <span class="text-red-500">*</span>
              </label>
              <input
                id="ec_relationship"
                v-model="form.emergency_contact.relationship"
                type="text"
                maxlength="100"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="e.g. Spouse, Mother, Sibling"
              />
              <p v-if="serverErrors['emergency_contact.relationship']" class="mt-1 text-sm text-red-600">{{ serverErrors['emergency_contact.relationship'][0] }}</p>
            </div>
            <div>
              <label for="ec_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Mobile Number <span class="text-red-500">*</span>
              </label>
              <input
                id="ec_phone"
                v-model="form.emergency_contact.phone_primary"
                type="tel"
                maxlength="50"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
                placeholder="+63-917-123-4567"
              />
              <p v-if="serverErrors['emergency_contact.phone_primary']" class="mt-1 text-sm text-red-600">{{ serverErrors['emergency_contact.phone_primary'][0] }}</p>
            </div>
          </div>
        </div>

        <div>
          <label for="cover_letter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Cover Letter <span class="text-red-500">*</span>
          </label>
          <textarea
            id="cover_letter"
            v-model="form.cover_letter"
            rows="5"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
            placeholder="Tell us why you're interested in this position and what makes you a great fit..."
          />
          <p class="mt-1 text-xs text-gray-400">{{ form.cover_letter.trim().length }} / 10,000 characters (minimum 20)</p>
          <p v-if="serverErrors.cover_letter" class="mt-1 text-sm text-red-600">{{ serverErrors.cover_letter[0] }}</p>
        </div>
      </div>

      <!-- Step 3: Education -->
      <div v-show="currentStep === 3">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Education</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          Add your educational background. <span class="font-medium text-gray-700 dark:text-gray-300">Both an elementary and a secondary entry are required</span> (tertiary is optional).
        </p>
        <p v-if="form.educations.length > 0" class="text-xs text-green-600 dark:text-green-400 mb-4">
          ✓ {{ form.educations.length }} {{ form.educations.length === 1 ? 'entry' : 'entries' }} added
        </p>
        <p v-else class="text-xs text-amber-600 dark:text-amber-400 mb-4">
          Please add at least one education entry to continue.
        </p>

        <EntryFormSection
          :editing-index="editingEducation"
          @add="addEducation"
          @save="saveEducation"
          @cancel="cancelEditEducation"
        >
          <EducationEntryForm :entry="tempEducation" :index="editingEducation ?? form.educations.length" :errors="serverErrors" />
        </EntryFormSection>
        <p v-if="hasPendingEntry && currentStep === 3" class="mt-2 text-sm text-amber-600">
          Please click "Add Entry" to save this entry before continuing.
        </p>

        <EntryListPreview
          :entries="form.educations"
          empty-message="No education entries added yet."
          @edit="editEducation"
          @remove="removeEducation"
        >
          <template #summary="{ entry }">
            {{ eduSummary(entry as EducationEntry) }}
          </template>
        </EntryListPreview>
      </div>

      <!-- Step 4: Work Experience -->
      <div v-show="currentStep === 4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Work Experience</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          Add your work history. <span class="font-medium text-gray-700 dark:text-gray-300">At least 1 entry is required.</span>
        </p>
        <p v-if="form.work_experiences.length > 0" class="text-xs text-green-600 dark:text-green-400 mb-4">
          ✓ {{ form.work_experiences.length }} {{ form.work_experiences.length === 1 ? 'entry' : 'entries' }} added
        </p>
        <p v-else class="text-xs text-amber-600 dark:text-amber-400 mb-4">
          Please add at least one work experience entry to continue.
        </p>

        <EntryFormSection
          :editing-index="editingExperience"
          @add="addExperience"
          @save="saveExperience"
          @cancel="cancelEditExperience"
        >
          <WorkExperienceEntryForm :entry="tempExperience" :index="editingExperience ?? form.work_experiences.length" :errors="serverErrors" />
        </EntryFormSection>
        <p v-if="hasPendingEntry && currentStep === 4" class="mt-2 text-sm text-amber-600">
          Please click "Add Entry" to save this entry before continuing.
        </p>

        <EntryListPreview
          :entries="form.work_experiences"
          empty-message="No work experience entries added yet."
          @edit="editExperience"
          @remove="removeExperience"
        >
          <template #summary="{ entry }">
            <div>{{ expSummary(entry as WorkExperienceEntry) }}</div>
            <p
              v-if="expIncompleteFields(entry as WorkExperienceEntry).length"
              class="mt-1 text-xs text-amber-600 dark:text-amber-400"
            >
              ⚠ Incomplete — missing {{ expIncompleteFields(entry as WorkExperienceEntry).join(', ') }}. Click Edit to finish.
            </p>
          </template>
        </EntryListPreview>
      </div>

      <!-- Step 5: Skills -->
      <div v-show="currentStep === 5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Skills</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          Add your skills and proficiency levels. <span class="font-medium text-gray-700 dark:text-gray-300">Minimum 5 skills required.</span>
        </p>
        <p v-if="form.skills.length >= 5" class="text-xs text-green-600 dark:text-green-400 mb-4">
          ✓ {{ form.skills.length }} skills added
        </p>
        <p v-else class="text-xs text-amber-600 dark:text-amber-400 mb-4">
          {{ form.skills.length }} of 5 required skills added — {{ 5 - form.skills.length }} more needed.
        </p>

        <EntryFormSection
          :editing-index="editingSkill"
          @add="addSkill"
          @save="saveSkill"
          @cancel="cancelEditSkill"
        >
          <SkillEntryForm :entry="tempSkill" :index="editingSkill ?? form.skills.length" :errors="serverErrors" />
        </EntryFormSection>
        <p v-if="hasPendingEntry && currentStep === 5" class="mt-2 text-sm text-amber-600">
          Please click "Add Entry" to save this entry before continuing.
        </p>

        <EntryListPreview
          :entries="form.skills"
          empty-message="No skills added yet."
          @edit="editSkillEntry"
          @remove="removeSkill"
        >
          <template #summary="{ entry }">
            {{ skillSummary(entry as SkillEntry) }}
          </template>
        </EntryListPreview>
      </div>

      <!-- Step 6: References -->
      <div v-show="currentStep === 6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">References</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">
          Add professional references. <span class="font-medium text-gray-700 dark:text-gray-300">Minimum 2 references required.</span>
        </p>
        <p v-if="form.references.length >= 2" class="text-xs text-green-600 dark:text-green-400 mb-4">
          ✓ {{ form.references.length }} references added
        </p>
        <p v-else class="text-xs text-amber-600 dark:text-amber-400 mb-4">
          {{ form.references.length }} of 2 required references added — {{ 2 - form.references.length }} more needed.
        </p>

        <EntryFormSection
          :editing-index="editingReference"
          @add="addReference"
          @save="saveReference"
          @cancel="cancelEditReference"
        >
          <ReferenceEntryForm :entry="tempReference" :index="editingReference ?? form.references.length" :errors="serverErrors" />
        </EntryFormSection>
        <p v-if="hasPendingEntry && currentStep === 6" class="mt-2 text-sm text-amber-600">
          Please click "Add Entry" to save this entry before continuing.
        </p>

        <EntryListPreview
          :entries="form.references"
          empty-message="No references added yet."
          @edit="editReference"
          @remove="removeReference"
        >
          <template #summary="{ entry }">
            {{ refSummary(entry as ReferenceEntry) }}
          </template>
        </EntryListPreview>
      </div>

      <!-- Step 7: Photo -->
      <div v-show="currentStep === 7">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Applicant Photo</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          <template v-if="editMode">
            Replace your photo if it has changed. <span class="font-medium text-gray-700 dark:text-gray-300">Optional</span> — your existing photo will be kept if you skip this.
          </template>
          <template v-else>
            Upload a professional photo. <span class="font-medium text-gray-700 dark:text-gray-300">Required.</span>
          </template>
          JPG or PNG, max 2MB.
        </p>
        <PhotoUploader
          :model-value="form.photo"
          :error="serverErrors.photo?.[0]"
          @update:model-value="form.photo = $event"
        />
        <p v-if="!form.photo && !editMode" class="mt-3 text-xs text-amber-600 dark:text-amber-400">
          Please upload a photo to continue.
        </p>
        <p v-if="serverErrors.photo" class="mt-1 text-sm text-red-600">{{ serverErrors.photo[0] }}</p>
      </div>

      <!-- Step 8: Review + Email Verification -->
      <div v-show="currentStep === 8" class="space-y-6">
        <div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review Your Application</h2>
          <ReviewResumePreview :form="form" />
        </div>

        <!-- Email Verification (hidden in edit mode — token authorises the submit) -->
        <div v-if="!editMode" class="rounded-xl border dark:border-zinc-700"
          :class="emailVerified ? 'border-green-200 bg-green-50 dark:bg-green-900/20' : 'border-amber-200 bg-amber-50 dark:bg-amber-900/20'">
          <div class="p-5">
            <!-- Verified state -->
            <div v-if="emailVerified" class="flex items-center gap-3">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-800">
                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-semibold text-green-800 dark:text-green-300">Email Verified</p>
                <p class="text-sm text-green-700 dark:text-green-400">{{ form.email }} has been verified. You're ready to submit.</p>
              </div>
            </div>

            <!-- Unverified state -->
            <div v-else>
              <div class="flex items-start gap-3 mb-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-800">
                  <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Verify Your Email</p>
                  <p class="text-sm text-amber-700 dark:text-amber-400">
                    We need to confirm <strong>{{ form.email }}</strong> is a real, active email before you can submit.
                  </p>
                </div>
              </div>

              <!-- Error -->
              <div v-if="otpError" class="mb-3 rounded-md bg-red-50 border border-red-200 px-3 py-2 dark:bg-red-900/20 dark:border-red-800">
                <p class="text-sm text-red-700 dark:text-red-400">{{ otpError }}</p>
              </div>

              <!-- Send OTP button -->
              <div v-if="!otpSent" class="flex items-center gap-3">
                <button
                  type="button"
                  :disabled="otpSending || !form.email"
                  class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-amber-700 dark:hover:bg-amber-600"
                  @click="sendOtp"
                >
                  <svg v-if="otpSending" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  {{ otpSending ? 'Sending...' : 'Send Verification Code' }}
                </button>
                <p class="text-xs text-amber-700 dark:text-amber-400">A 6-digit code will be sent to your email.</p>
              </div>

              <!-- Code input (after send) -->
              <div v-else class="space-y-3">
                <p class="text-sm text-amber-700 dark:text-amber-400">
                  A verification code was sent to <strong>{{ form.email }}</strong>. Enter it below.
                </p>
                <div class="flex items-center gap-3">
                  <input
                    v-model="otpCode"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="000000"
                    class="w-32 rounded-md border-gray-300 text-center text-lg font-mono tracking-widest shadow-sm focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                  />
                  <button
                    type="button"
                    :disabled="otpVerifying || otpCode.length !== 6"
                    class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-amber-700 dark:hover:bg-amber-600"
                    @click="verifyOtp"
                  >
                    <svg v-if="otpVerifying" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    {{ otpVerifying ? 'Verifying...' : 'Verify' }}
                  </button>
                </div>
                <!-- Resend timer -->
                <div v-if="otpCooldown > 0" class="rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-3 py-2">
                  <div class="flex items-center justify-between mb-1.5">
                    <div class="flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Resend available in
                    </div>
                    <span class="text-sm font-semibold tabular-nums text-amber-700 dark:text-amber-300">{{ otpCooldown }}s</span>
                  </div>
                  <div class="h-1 w-full overflow-hidden rounded-full bg-amber-200 dark:bg-amber-800">
                    <div
                      class="h-full rounded-full bg-amber-500 dark:bg-amber-400 transition-all duration-1000 ease-linear"
                      :style="{ width: `${(otpCooldown / 180) * 100}%` }"
                    />
                  </div>
                </div>
                <button
                  v-else
                  type="button"
                  class="text-xs text-amber-600 dark:text-amber-400 underline hover:no-underline"
                  @click="sendOtp"
                >
                  Resend code
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms & Conditions / Data Privacy consent -->
        <div
          class="rounded-xl border p-5 dark:border-zinc-700"
          :class="termsAccepted
            ? 'border-green-200 bg-green-50 dark:bg-green-900/20'
            : 'border-gray-200 bg-white dark:bg-zinc-800'"
        >
          <label class="flex cursor-pointer items-start gap-3">
            <input
              v-model="termsAccepted"
              type="checkbox"
              class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-c-primary focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-900"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300">
              I have read, understood, and agree to the
              <button
                type="button"
                class="font-medium text-c-primary hover:underline"
                @click.prevent="termsModalOpen = true"
              >
                Terms and Conditions and Data Privacy Notice
              </button>
              issued under Republic Act No. 10173 (Data Privacy Act of 2012). I freely give my
              consent to the collection and processing of the personal information I have provided
              for the purposes described therein.
              <span class="text-red-500">*</span>
            </span>
          </label>
        </div>
      </div>

      <!-- Navigation buttons -->
      <div v-if="currentStep > 1" class="mt-8 flex items-center justify-between">
        <button
          type="button"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-gray-200 dark:hover:bg-zinc-700"
          @click="prevStep"
        >
          &larr; Previous
        </button>

        <button
          v-if="currentStep < 8"
          type="submit"
          :disabled="!canProceed || hasPendingEntry"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-c-primary border border-transparent rounded-md hover:bg-c-primary-hover disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next &rarr;
        </button>

        <button
          v-else
          type="submit"
          :disabled="submitting || !emailVerified || !termsAccepted"
          class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-c-primary border border-transparent rounded-md hover:bg-c-primary-hover disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ submitting ? 'Submitting...' : 'Submit Application' }}
        </button>
      </div>
    </form>

    <TermsAndConditionsModal
      :open="termsModalOpen"
      :company-name="companyName"
      @close="termsModalOpen = false"
    />
  </div>
</template>
