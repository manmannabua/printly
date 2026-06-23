<script setup lang="ts">
import type { SkillEntry } from '../composables/useApplicationForm'

defineProps<{
  entry: SkillEntry
  index: number
  errors: Record<string, string[]>
}>()

const categories = [
  { value: 'technical', label: 'Technical' },
  { value: 'soft_skill', label: 'Soft Skill' },
  { value: 'language', label: 'Language' },
  { value: 'certification', label: 'Certification' },
  { value: 'other', label: 'Other' },
]

function getError(errors: Record<string, string[]>, index: number, field: string): string | undefined {
  return errors[`skills.${index}.${field}`]?.[0]
}
</script>

<template>
  <div class="space-y-3 pr-14">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label :for="`skill-name-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Skill Name <span class="text-red-500">*</span>
        </label>
        <input
          :id="`skill-name-${index}`"
          v-model="entry.name"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
          placeholder="e.g. JavaScript, Leadership"
        />
        <p v-if="getError(errors, index, 'name')" class="mt-1 text-sm text-red-600">
          {{ getError(errors, index, 'name') }}
        </p>
      </div>
      <div>
        <label :for="`skill-category-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
        <select
          :id="`skill-category-${index}`"
          v-model="entry.category"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
        >
          <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
        </select>
      </div>
    </div>

    <div>
      <label :for="`skill-prof-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Proficiency Level <span class="text-red-500">*</span>
        <span class="ml-2 text-c-primary font-bold">{{ entry.proficiency_level }}%</span>
      </label>
      <input
        :id="`skill-prof-${index}`"
        v-model.number="entry.proficiency_level"
        type="range"
        min="0"
        max="100"
        step="5"
        class="skill-range mt-1 block w-full h-2 rounded-lg appearance-none cursor-pointer"
        :style="`background: linear-gradient(to right, var(--c-primary) ${entry.proficiency_level}%, #3f3f46 ${entry.proficiency_level}%)`"
      />
      <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-1">
        <span>Beginner</span>
        <span>Intermediate</span>
        <span>Expert</span>
      </div>
      <p v-if="getError(errors, index, 'proficiency_level')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'proficiency_level') }}
      </p>
    </div>

    <div>
      <label :for="`skill-years-${index}`" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Years of Experience <span class="text-red-500">*</span>
      </label>
      <input
        :id="`skill-years-${index}`"
        v-model="entry.years_experience"
        type="number"
        min="0"
        max="50"
        step="0.5"
        class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm hover:border-c-primary focus:border-c-primary focus:ring-1 focus:ring-c-primary text-base py-2.5 px-3 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
        placeholder="e.g. 3"
      />
      <p v-if="getError(errors, index, 'years_experience')" class="mt-1 text-sm text-red-600">
        {{ getError(errors, index, 'years_experience') }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.skill-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--c-primary);
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.skill-range::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--c-primary);
  cursor: pointer;
  border: none;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
</style>
