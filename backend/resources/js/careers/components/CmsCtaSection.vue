<script setup lang="ts">
import { careersUrl } from '../utils/careersUrl'

defineProps<{
  data: {
    title?: string
    subtitle?: string
    button_text?: string
    button_url?: string
  }
}>()
</script>

<template>
  <section class="rounded-2xl bg-c-gradient-r px-6 py-12 text-center text-white sm:px-12 sm:py-16">
    <h2 v-if="data.title" class="text-2xl font-bold sm:text-3xl">{{ data.title }}</h2>
    <p v-if="data.subtitle" class="mx-auto mt-3 max-w-xl text-c-primary-light">{{ data.subtitle }}</p>
    <a
      v-if="data.button_text"
      :href="data.button_url || careersUrl()"
      class="mt-6 inline-block rounded-lg bg-white px-8 py-3 text-sm font-semibold text-c-primary shadow-lg transition-transform hover:scale-105"
    >
      {{ data.button_text }}
    </a>
  </section>
</template>
