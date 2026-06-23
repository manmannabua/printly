<script setup lang="ts">
</script>

<template>
  <div class="inline-flex rounded-lg shadow-sm [&>button:first-child]:rounded-r-none [&>button:last-child]:rounded-l-none [&>button:not(:first-child):not(:last-child)]:rounded-none [&>button:not(:first-child)]:border-l-0">
    <slot />
  </div>
</template>
