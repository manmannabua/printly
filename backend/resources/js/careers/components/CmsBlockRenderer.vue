<script setup lang="ts">
import CmsRichTextBlock from './CmsRichTextBlock.vue'
import CmsHeroBlock from './CmsHeroBlock.vue'
import CmsValuesSection from './CmsValuesSection.vue'
import CmsBenefitsSection from './CmsBenefitsSection.vue'
import CmsTestimonialsSection from './CmsTestimonialsSection.vue'
import CmsCtaSection from './CmsCtaSection.vue'
import CmsImageBlock from './CmsImageBlock.vue'
import CmsFeaturedJobs from './CmsFeaturedJobs.vue'
import type { ContentBlock } from '../types/careers'

defineProps<{
  block: ContentBlock
}>()

const componentMap: Record<string, unknown> = {
  rich_text: CmsRichTextBlock,
  hero: CmsHeroBlock,
  values: CmsValuesSection,
  benefits: CmsBenefitsSection,
  testimonials: CmsTestimonialsSection,
  cta: CmsCtaSection,
  image: CmsImageBlock,
  featured_jobs: CmsFeaturedJobs,
}
</script>

<template>
  <component
    :is="componentMap[block.type] as any"
    v-if="componentMap[block.type]"
    :data="block.data"
  />
</template>
