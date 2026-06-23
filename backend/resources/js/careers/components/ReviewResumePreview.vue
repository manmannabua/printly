<script setup lang="ts">
import { computed, onBeforeUnmount } from 'vue'
import type { ApplicationFormData } from '../composables/useApplicationForm'

const props = defineProps<{
  form: ApplicationFormData
}>()

const photoPreviewUrl = computed(() => {
  if (props.form.photo) {
    return URL.createObjectURL(props.form.photo)
  }
  return null
})

onBeforeUnmount(() => {
  if (photoPreviewUrl.value) {
    URL.revokeObjectURL(photoPreviewUrl.value)
  }
})

const SKILL_CATEGORIES: Record<string, string> = {
  technical: 'Technical',
  soft_skill: 'Soft Skill',
  language: 'Language',
  certification: 'Certification',
  other: 'Other',
}

function formatDate(date: string): string {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
}

function formatYear(date: string): string {
  if (!date) return ''
  return new Date(date).getFullYear().toString()
}

function formatDuration(start: string, end: string, isCurrent: boolean): string {
  if (!start) return ''
  const from = new Date(start)
  const to = isCurrent ? new Date() : (end ? new Date(end) : null)
  if (!to) return ''
  const months = (to.getFullYear() - from.getFullYear()) * 12 + (to.getMonth() - from.getMonth())
  if (months <= 0) return ''
  const yrs = Math.floor(months / 12)
  const mos = months % 12
  if (yrs === 0) return `${mos} mo${mos !== 1 ? 's' : ''}`
  if (mos === 0) return `${yrs} yr${yrs !== 1 ? 's' : ''}`
  return `${yrs} yr${yrs !== 1 ? 's' : ''} ${mos} mo${mos !== 1 ? 's' : ''}`
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <!-- Header -->
    <div class="border-b border-c-light bg-c-primary-light px-6 py-4 text-center dark:border-zinc-700 dark:bg-zinc-700">
      <img
        v-if="photoPreviewUrl"
        :src="photoPreviewUrl"
        alt="Applicant photo"
        class="mx-auto mb-3 h-28 w-28 rounded-full object-cover border-2 border-c-light dark:border-zinc-500"
      />
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ') }}</h2>
      <p class="mt-1 text-base text-gray-600 dark:text-gray-300">
        {{ form.email }}
        <span v-if="form.phone"> &middot; {{ form.phone }}</span>
      </p>
      <p v-if="form.address" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ form.address }}</p>
    </div>

    <div class="space-y-2 px-6 pt-4 text-sm">
      <h3 class="text-lg font-bold text-c-primary">Personal Information</h3>
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-600 dark:text-gray-300">
        <div v-if="form.date_of_birth" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Date of Birth:</dt><dd>{{ form.date_of_birth }}</dd></div>
        <div v-if="form.birth_place" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Birth Place:</dt><dd>{{ form.birth_place }}</dd></div>
        <div v-if="form.nationality" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Nationality:</dt><dd>{{ form.nationality }}</dd></div>
        <div v-if="form.gender" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Gender:</dt><dd class="capitalize">{{ form.gender }}</dd></div>
        <div v-if="form.civil_status" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Civil Status:</dt><dd class="capitalize">{{ form.civil_status }}</dd></div>
        <div v-if="form.father_name" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Father's Name:</dt><dd>{{ form.father_name }}</dd></div>
        <div v-if="form.mother_name" class="flex gap-2"><dt class="font-medium min-w-[8rem]">Mother's Name:</dt><dd>{{ form.mother_name }}</dd></div>
      </dl>
    </div>

    <div v-if="form.government_ids.tin || form.government_ids.sss || form.government_ids.philhealth || form.government_ids.pagibig" class="space-y-2 px-6 pt-4 text-sm">
      <h3 class="text-lg font-bold text-c-primary">Government IDs</h3>
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-gray-600 dark:text-gray-300">
        <div v-if="form.government_ids.tin" class="flex gap-2"><dt class="font-medium min-w-[6rem]">TIN:</dt><dd>{{ form.government_ids.tin }}</dd></div>
        <div v-if="form.government_ids.sss" class="flex gap-2"><dt class="font-medium min-w-[6rem]">SSS:</dt><dd>{{ form.government_ids.sss }}</dd></div>
        <div v-if="form.government_ids.philhealth" class="flex gap-2"><dt class="font-medium min-w-[6rem]">PhilHealth:</dt><dd>{{ form.government_ids.philhealth }}</dd></div>
        <div v-if="form.government_ids.pagibig" class="flex gap-2"><dt class="font-medium min-w-[6rem]">HDMF:</dt><dd>{{ form.government_ids.pagibig }}</dd></div>
      </dl>
    </div>

    <div v-if="form.emergency_contact.name || form.emergency_contact.phone_primary" class="space-y-2 px-6 pt-4 text-sm">
      <h3 class="text-lg font-bold text-c-primary">Emergency Contact</h3>
      <p class="text-gray-700 dark:text-gray-300"><span class="font-medium">{{ form.emergency_contact.name }}</span><span v-if="form.emergency_contact.relationship"> &middot; {{ form.emergency_contact.relationship }}</span></p>
      <p class="text-gray-500 dark:text-gray-400">{{ form.emergency_contact.phone_primary }}</p>
    </div>

    <div class="space-y-4 px-6 py-4">
      <!-- Cover Letter -->
      <div v-if="form.cover_letter">
        <h3 class="text-lg font-bold text-c-primary">Cover Letter</h3>
        <p class="mt-2 whitespace-pre-wrap text-base text-gray-700 dark:text-gray-300">{{ form.cover_letter }}</p>
        <hr class="mt-4 border-gray-200 dark:border-zinc-700">
      </div>

      <!-- Work Experience -->
      <div v-if="form.work_experiences.length > 0">
        <h3 class="text-lg font-bold text-c-primary">Work Experience</h3>
        <div v-for="(exp, i) in form.work_experiences" :key="i" class="mt-2">
          <div class="flex items-baseline justify-between">
            <p class="text-base font-semibold text-gray-900 dark:text-white">
              {{ exp.job_title }}
              <span v-if="formatDuration(exp.start_date, exp.end_date, exp.is_current)" class="ml-1.5 text-sm font-normal text-gray-400 dark:text-gray-500">({{ formatDuration(exp.start_date, exp.end_date, exp.is_current) }})</span>
            </p>
            <p class="text-base text-gray-500 dark:text-gray-400">
              {{ formatDate(exp.start_date) }}
              &ndash;
              {{ exp.is_current ? 'Present' : formatDate(exp.end_date) }}
            </p>
          </div>
          <p class="text-base text-gray-600 dark:text-gray-300">{{ exp.company }}</p>
          <p v-if="exp.company_address" class="text-sm text-gray-500 dark:text-gray-400">{{ exp.company_address }}</p>
          <div v-if="exp.description" class="review-rich-content mt-0.5 text-base text-gray-500 dark:text-gray-400" v-html="exp.description" />
          <p v-if="exp.reason_for_leaving" class="mt-0.5 text-sm text-gray-400 dark:text-gray-500">
            <span class="font-medium text-gray-500 dark:text-gray-400">Reason for leaving:</span> {{ exp.reason_for_leaving }}
          </p>
        </div>
        <hr class="mt-4 border-gray-200 dark:border-zinc-700">
      </div>

      <!-- Education -->
      <div v-if="form.educations.length > 0">
        <h3 class="text-lg font-bold text-c-primary">Education</h3>
        <div v-for="(edu, i) in form.educations" :key="i" class="mt-2">
          <div class="flex items-baseline justify-between">
            <p class="text-base font-semibold text-gray-900 dark:text-white">
              {{ edu.degree }}<template v-if="edu.field_of_study"> in {{ edu.field_of_study }}</template>
            </p>
            <p class="text-base text-gray-500 dark:text-gray-400">
              {{ formatYear(edu.start_date) }}
              &ndash;
              {{ edu.is_current ? 'Present' : formatYear(edu.end_date) }}
            </p>
          </div>
          <p class="text-base text-gray-600 dark:text-gray-300">{{ edu.institution }}</p>
          <p v-if="edu.level || edu.status" class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ [edu.level, edu.status].filter(Boolean).join(' · ') }}</p>
        </div>
        <hr class="mt-4 border-gray-200 dark:border-zinc-700">
      </div>

      <!-- Skills -->
      <div v-if="form.skills.length > 0">
        <h3 class="text-lg font-bold text-c-primary">Skills</h3>
        <div class="mt-2 space-y-1.5">
          <div v-for="(skill, i) in form.skills" :key="i" class="flex items-center gap-3">
            <span class="w-28 text-base font-medium text-gray-700 dark:text-gray-300">{{ skill.name }}</span>
            <div class="flex-1">
              <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700">
                <div
                  class="h-full rounded-full bg-c-primary"
                  :style="{ width: `${skill.proficiency_level}%` }"
                />
              </div>
            </div>
            <span class="w-10 text-right text-sm text-gray-500 dark:text-gray-400">{{ skill.proficiency_level }}%</span>
          </div>
        </div>
        <hr class="mt-4 border-gray-200 dark:border-zinc-700">
      </div>

      <!-- References -->
      <div v-if="form.references.length > 0">
        <h3 class="text-lg font-bold text-c-primary">References</h3>
        <div v-for="(ref, i) in form.references" :key="i" class="mt-2">
          <p class="text-base font-semibold text-gray-900 dark:text-white">{{ ref.name }}</p>
          <p class="text-base text-gray-600 dark:text-gray-300">
            {{ ref.relationship }}<template v-if="ref.company">, {{ ref.company }}</template>
          </p>
          <p v-if="ref.email || ref.phone" class="text-base text-gray-500 dark:text-gray-400">
            {{ [ref.email, ref.phone].filter(Boolean).join(' &middot; ') }}
          </p>
        </div>
      </div>

      <!-- Resume file -->
      <div v-if="form.resume" class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ form.resume.name }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.review-rich-content :deep(ul) {
  list-style-type: disc;
  padding-left: 1.25rem;
}
.review-rich-content :deep(ol) {
  list-style-type: decimal;
  padding-left: 1.25rem;
}
.review-rich-content :deep(p) {
  margin: 0;
}
.review-rich-content :deep(li p) {
  margin: 0;
}
</style>
