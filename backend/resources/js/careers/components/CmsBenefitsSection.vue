<script setup lang="ts">
defineProps<{
  data: {
    title?: string
    items?: Array<{ title?: string; description?: string }>
  }
}>()
</script>

<template>
  <section class="py-8">
    <h2 v-if="data.title" class="mb-8 text-center text-2xl font-bold text-gray-900 sm:text-3xl">{{ data.title }}</h2>
    <div v-if="data.items?.length" class="grid gap-4 sm:grid-cols-2">
      <div
        v-for="(item, idx) in data.items"
        :key="idx"
        class="flex gap-3 rounded-lg border border-gray-100 bg-white p-5 shadow-sm"
      >
        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100">
          <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <h3 v-if="item.title" class="font-semibold text-gray-900">{{ item.title }}</h3>
          <p v-if="item.description" class="mt-1 text-sm text-gray-600">{{ item.description }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
