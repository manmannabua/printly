<script setup lang="ts">
import type { EducationEntry } from '../composables/useApplicationForm'

defineProps<{
  entry: EducationEntry
  index: number
  errors: Record<string, string[]>
}>()

function getError(errors: Record<string, string[]>, index: number, field: string): string | undefined {
  return errors[`educations.${index}.${field}`]?.[0]
}
</script>

<template>
  <div class="space-y-3 pr-14">
    <div>
      <label :for="`edu-institution-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Institution <span class="text-red-500">*</span>
      </label>
      <input
        :id="`edu-institution-${index}`"
        v-model="entry.institution"
        type="text"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
        placeholder="University or school name"
      />
      <p v-if="getError(errors, index, 'institution')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'institution') }}
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`edu-level-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Level <span class="text-red-500">*</span>
        </label>
        <select
          :id="`edu-level-${index}`"
          v-model="entry.level"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
        >
          <option value="">— Select —</option>
          <option value="tertiary">Tertiary</option>
          <option value="secondary">Secondary</option>
          <option value="elementary">Elementary</option>
        </select>
        <p v-if="getError(errors, index, 'level')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'level') }}
        </p>
      </div>
      <div>
        <label :for="`edu-status-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Status <span class="text-red-500">*</span>
        </label>
        <select
          :id="`edu-status-${index}`"
          v-model="entry.status"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
        >
          <option value="">— Select —</option>
          <option value="graduated">Graduated</option>
          <option value="undergraduate">Undergraduate</option>
          <option value="ongoing">Ongoing</option>
        </select>
        <p v-if="getError(errors, index, 'status')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'status') }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`edu-degree-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Degree</label>
        <input
          :id="`edu-degree-${index}`"
          v-model="entry.degree"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="e.g. Bachelor of Science"
        />
      </div>
      <div>
        <label :for="`edu-field-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Field of Study</label>
        <input
          :id="`edu-field-${index}`"
          v-model="entry.field_of_study"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="e.g. Computer Science"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`edu-start-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
        <input
          :id="`edu-start-${index}`"
          v-model="entry.start_date"
          type="date"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
        />
      </div>
      <div>
        <label :for="`edu-end-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
        <input
          :id="`edu-end-${index}`"
          v-model="entry.end_date"
          type="date"
          :disabled="entry.is_current"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 disabled:bg-gray-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:disabled:bg-zinc-700"
        />
      </div>
    </div>

    <label class="flex items-center gap-2">
      <input v-model="entry.is_current" type="checkbox" class="rounded border-gray-300 text-c-primary accent-c-primary focus:ring-c-primary" />
      <span class="text-sm text-gray-700 dark:text-gray-300">Currently studying here</span>
    </label>

    <div>
      <label :for="`edu-desc-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
      <textarea
        :id="`edu-desc-${index}`"
        v-model="entry.description"
        rows="2"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
        placeholder="Additional details (optional)"
      />
    </div>
  </div>
</template>
