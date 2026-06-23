<script setup lang="ts">
defineProps<{
  data: {
    image_url?: string
    image_alt?: string
    alt?: string
    caption?: string
  }
}>()
</script>

<template>
  <figure v-if="data.image_url" class="my-4">
    <img
      :src="data.image_url"
      :alt="data.alt || data.image_alt || ''"
      class="mx-auto max-w-full rounded-lg shadow-sm"
    />
    <figcaption v-if="data.caption" class="mt-2 text-center text-sm text-gray-500">
      {{ data.caption }}
    </figcaption>
  </figure>
</template>
