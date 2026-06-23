<script setup lang="ts">
// Minimal, mobile-first shell for the public storefront + order tracking.
// No app chrome, no auth — just a centered column that reads well on a phone.
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-black">
    <div class="mx-auto w-full max-w-lg px-4 pb-24 pt-4">
      <router-view />
    </div>
  </div>
</template>
