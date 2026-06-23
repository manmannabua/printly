<script setup lang="ts">
import type { WorkExperienceEntry } from '../composables/useApplicationForm'
import SimpleRichEditor from './SimpleRichEditor.vue'

defineProps<{
  entry: WorkExperienceEntry
  index: number
  errors: Record<string, string[]>
}>()

function getError(errors: Record<string, string[]>, index: number, field: string): string | undefined {
  return errors[`work_experiences.${index}.${field}`]?.[0]
}
</script>

<template>
  <div class="space-y-3 pr-14">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`exp-company-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Company <span class="text-red-500">*</span>
        </label>
        <input
          :id="`exp-company-${index}`"
          v-model="entry.company"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="Company name"
        />
        <p v-if="getError(errors, index, 'company')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'company') }}
        </p>
      </div>
      <div>
        <label :for="`exp-title-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Job Title <span class="text-red-500">*</span>
        </label>
        <input
          :id="`exp-title-${index}`"
          v-model="entry.job_title"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="Your role/position"
        />
        <p v-if="getError(errors, index, 'job_title')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'job_title') }}
        </p>
      </div>
    </div>

    <div>
      <label :for="`exp-company-address-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Company Address <span class="text-red-500">*</span>
      </label>
      <input
        :id="`exp-company-address-${index}`"
        v-model="entry.company_address"
        type="text"
        maxlength="500"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
        placeholder="e.g. 500 Acme Ave, Makati City"
      />
      <p v-if="getError(errors, index, 'company_address')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'company_address') }}
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`exp-start-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Start Date <span class="text-red-500">*</span>
        </label>
        <input
          :id="`exp-start-${index}`"
          v-model="entry.start_date"
          type="date"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
        />
        <p v-if="getError(errors, index, 'start_date')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'start_date') }}
        </p>
      </div>
      <div>
        <label :for="`exp-end-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
        <input
          :id="`exp-end-${index}`"
          v-model="entry.end_date"
          type="date"
          :disabled="entry.is_current"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 disabled:bg-gray-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:disabled:bg-zinc-700"
        />
      </div>
    </div>

    <label class="flex items-center gap-2">
      <input v-model="entry.is_current" type="checkbox" class="rounded border-gray-300 text-c-primary accent-c-primary focus:ring-c-primary" />
      <span class="text-sm text-gray-700 dark:text-gray-300">I currently work here</span>
    </label>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Description <span class="text-red-500">*</span>
      </label>
      <div class="mt-1">
        <SimpleRichEditor
          v-model="entry.description"
          placeholder="Describe your responsibilities and achievements"
        />
      </div>
      <p v-if="getError(errors, index, 'description')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'description') }}
      </p>
    </div>

    <div v-if="!entry.is_current">
      <label :for="`exp-reason-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Reason for Leaving <span class="text-red-500">*</span>
      </label>
      <input
        :id="`exp-reason-${index}`"
        v-model="entry.reason_for_leaving"
        type="text"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
        placeholder="e.g. Career growth, contract ended, relocation"
        maxlength="500"
      />
      <p v-if="getError(errors, index, 'reason_for_leaving')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'reason_for_leaving') }}
      </p>
    </div>
  </div>
</template>
