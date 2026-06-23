<script setup lang="ts">
defineProps<{
  data: {
    title?: string
    items?: Array<{ name?: string; role?: string; quote?: string; image_url?: string | null }>
  }
}>()
</script>

<template>
  <section class="py-8">
    <h2 v-if="data.title" class="mb-8 text-center text-2xl font-bold text-gray-900 sm:text-3xl">{{ data.title }}</h2>
    <div v-if="data.items?.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="(item, idx) in data.items"
        :key="idx"
        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm"
      >
        <svg class="mb-3 h-8 w-8 text-c-primary-light" fill="currentColor" viewBox="0 0 32 32">
          <path d="M9.352 4C4.456 7.456 1 13.12 1 19.36c0 5.088 3.072 8.064 6.624 8.064 3.36 0 5.856-2.688 5.856-5.856 0-3.168-2.208-5.472-5.088-5.472-.576 0-1.344.096-1.536.192.48-3.264 3.552-7.104 6.624-9.024L9.352 4zm16.512 0c-4.8 3.456-8.256 9.12-8.256 15.36 0 5.088 3.072 8.064 6.624 8.064 3.264 0 5.856-2.688 5.856-5.856 0-3.168-2.304-5.472-5.184-5.472-.576 0-1.248.096-1.44.192.48-3.264 3.456-7.104 6.528-9.024L25.864 4z" />
        </svg>
        <p class="mb-4 text-sm italic text-gray-600">"{{ item.quote }}"</p>
        <div class="flex items-center gap-3">
          <img
            v-if="item.image_url"
            :src="item.image_url"
            :alt="item.name"
            class="h-10 w-10 rounded-full object-cover"
          />
          <div
            v-else
            class="flex h-10 w-10 items-center justify-center rounded-full bg-c-primary-light text-sm font-semibold text-c-primary"
          >
            {{ (item.name || '?').charAt(0) }}
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
            <p v-if="item.role" class="text-xs text-gray-500">{{ item.role }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
