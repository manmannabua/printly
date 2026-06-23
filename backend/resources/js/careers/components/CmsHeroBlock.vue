<script setup lang="ts">
import { careersUrl } from '../utils/careersUrl'

defineProps<{
  data: {
    title?: string
    subtitle?: string
    cta_text?: string
    cta_url?: string
    image_url?: string | null
  }
}>()
</script>

<template>
  <section
    class="relative overflow-hidden rounded-2xl bg-c-gradient px-6 py-16 text-center text-white sm:px-12 sm:py-24"
  >
    <img
      v-if="data.image_url"
      :src="data.image_url"
      alt=""
      class="absolute inset-0 h-full w-full object-cover opacity-20"
    />
    <div class="relative z-10">
      <h2 v-if="data.title" class="text-3xl font-bold sm:text-5xl">{{ data.title }}</h2>
      <p v-if="data.subtitle" class="mx-auto mt-4 max-w-2xl text-lg text-c-primary-light">{{ data.subtitle }}</p>
      <a
        v-if="data.cta_text"
        :href="data.cta_url || careersUrl()"
        class="mt-8 inline-block rounded-lg bg-white px-8 py-3 text-sm font-semibold text-c-primary shadow-lg transition-transform hover:scale-105"
      >
        {{ data.cta_text }}
      </a>
    </div>
  </section>
</template>
