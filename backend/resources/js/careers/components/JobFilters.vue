<script setup lang="ts">
import { ref, watch } from 'vue'
import FilterDropdown from './FilterDropdown.vue'

defineProps<{
  departments: { id: string; name: string }[]
  employmentTypes: { id: string; name: string }[]
}>()

const emit = defineEmits<{
  'update:search': [value: string]
  'update:department': [value: string | null]
  'update:employmentType': [value: string | null]
}>()

const search = ref('')
const departmentFilter = ref<string | null>(null)
const employmentTypeFilter = ref<string | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(search, (val) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => emit('update:search', val), 300)
})

function updateDepartment(val: string | null) {
  departmentFilter.value = val
  emit('update:department', val)
}

function updateEmploymentType(val: string | null) {
  employmentTypeFilter.value = val
  emit('update:employmentType', val)
}

const hasActiveFilters = () => departmentFilter.value !== null || employmentTypeFilter.value !== null

function clearFilters() {
  departmentFilter.value = null
  employmentTypeFilter.value = null
  emit('update:department', null)
  emit('update:employmentType', null)
}
</script>

<template>
  <div class="space-y-3">
    <!-- Search bar -->
    <div class="relative">
      <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input
        v-model="search"
        type="text"
        placeholder="Search positions by title, location, or department..."
        class="w-full rounded-xl border border-gray-300 bg-white py-3 pl-11 pr-4 text-sm shadow-sm transition-shadow focus:border-c-primary focus:outline-none focus:ring-2 focus:ring-c-primary-20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-500"
      />
    </div>

    <!-- Filter dropdowns -->
    <div v-if="departments.length > 0 || employmentTypes.length > 0" class="flex flex-wrap items-center gap-2">
      <FilterDropdown
        v-if="departments.length > 0"
        label="Department"
        :options="departments"
        :model-value="departmentFilter"
        @update:model-value="updateDepartment"
      />
      <FilterDropdown
        v-if="employmentTypes.length > 0"
        label="Type"
        :options="employmentTypes"
        :model-value="employmentTypeFilter"
        @update:model-value="updateEmploymentType"
      />
      <button
        v-if="hasActiveFilters()"
        type="button"
        class="ml-1 text-sm text-c-primary hover:text-c-primary-hover"
        @click="clearFilters"
      >
        Clear filters
      </button>
    </div>
  </div>
</template>
