<script setup lang="ts">
defineProps<{
  data: {
    title?: string
    items?: Array<{ icon?: string; title?: string; description?: string }>
  }
}>()

const iconMap: Record<string, string> = {
  heart: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
  shield: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
  lightbulb: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
}
</script>

<template>
  <section class="py-8">
    <h2 v-if="data.title" class="mb-8 text-center text-2xl font-bold text-gray-900 sm:text-3xl">{{ data.title }}</h2>
    <div v-if="data.items?.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="(item, idx) in data.items"
        :key="idx"
        class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
      >
        <div v-if="item.icon && iconMap[item.icon]" class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-c-primary-light">
          <svg class="h-6 w-6 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" :d="iconMap[item.icon]" />
          </svg>
        </div>
        <h3 v-if="item.title" class="mb-2 text-lg font-semibold text-gray-900">{{ item.title }}</h3>
        <p v-if="item.description" class="text-sm text-gray-600">{{ item.description }}</p>
      </div>
    </div>
  </section>
</template>
