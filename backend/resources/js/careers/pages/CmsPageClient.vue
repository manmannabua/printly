<script setup lang="ts">
import CmsBlockRenderer from '../components/CmsBlockRenderer.vue'
import HeroSection from '../components/HeroSection.vue'
import type { CmsPageData } from '../types/careers'

defineProps<{
  pageData: CmsPageData
}>()

const companyName = document.getElementById('careers-app')?.dataset.company ?? ''
</script>

<template>
  <div>
    <HeroSection
      :company-name="companyName"
      :job-count="0"
      :hero-title="pageData.title"
      :hero-subtitle="pageData.subtitle"
      :hero-text-align="pageData.hero_text_align ?? 'center'"
    />

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="space-y-8">
        <CmsBlockRenderer
          v-for="(block, index) in pageData.content"
          :key="index"
          :block="block"
        />
      </div>
    </div>
  </div>
</template>
