<script setup lang="ts">
defineProps<{
  data: { html?: string }
}>()
</script>

<template>
  <div
    v-if="data.html"
    class="prose prose-c-primary max-w-none"
    v-html="data.html"
  />
</template>
