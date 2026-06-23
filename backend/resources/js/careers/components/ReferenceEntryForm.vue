<script setup lang="ts">
import type { ReferenceEntry } from '../composables/useApplicationForm'

defineProps<{
  entry: ReferenceEntry
  index: number
  errors: Record<string, string[]>
}>()

function getError(errors: Record<string, string[]>, index: number, field: string): string | undefined {
  return errors[`references.${index}.${field}`]?.[0]
}
</script>

<template>
  <div class="space-y-3 pr-14">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`ref-name-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Full Name <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-name-${index}`"
          v-model="entry.name"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="Reference's full name"
        />
        <p v-if="getError(errors, index, 'name')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'name') }}
        </p>
      </div>
      <div>
        <label :for="`ref-rel-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Relationship <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-rel-${index}`"
          v-model="entry.relationship"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="e.g. Former Manager"
        />
        <p v-if="getError(errors, index, 'relationship')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'relationship') }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`ref-company-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Company <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-company-${index}`"
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
        <label :for="`ref-position-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Position <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-position-${index}`"
          v-model="entry.position"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="Their position/title"
        />
        <p v-if="getError(errors, index, 'position')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'position') }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`ref-email-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Email <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-email-${index}`"
          v-model="entry.email"
          type="email"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="reference@example.com"
        />
        <p v-if="getError(errors, index, 'email')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'email') }}
        </p>
      </div>
      <div>
        <label :for="`ref-phone-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Phone <span class="text-red-500">*</span>
        </label>
        <input
          :id="`ref-phone-${index}`"
          v-model="entry.phone"
          type="tel"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="+63-917-123-4567"
        />
        <p v-if="getError(errors, index, 'phone')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'phone') }}
        </p>
      </div>
    </div>
  </div>
</template>
