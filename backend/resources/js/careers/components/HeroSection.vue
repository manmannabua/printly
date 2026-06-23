<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    companyName: string
    jobCount: number
    heroTitle?: string | null
    heroSubtitle?: string | null
    heroTextAlign?: 'center' | 'left' | 'right'
  }>(),
  {
    heroTitle: null,
    heroSubtitle: null,
    heroTextAlign: 'center',
  },
)

const alignClass = computed(() => {
  switch (props.heroTextAlign) {
    case 'left': return 'text-left'
    case 'right': return 'text-right ml-auto'
    default: return 'text-center'
  }
})

const title = computed(() => props.heroTitle || 'Join Our Team')
const subtitle = computed(() =>
  props.heroSubtitle || `Build your career with ${props.companyName}. We're looking for talented people who want to make a difference.`,
)
</script>

<template>
  <section class="relative overflow-hidden bg-c-gradient py-16 sm:py-20">
    <!-- Decorative circles -->
    <div class="pointer-events-none absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/5" />
    <div class="pointer-events-none absolute -bottom-16 -right-16 h-56 w-56 rounded-full bg-white/5" />
    <div class="pointer-events-none absolute right-1/4 top-1/3 h-32 w-32 rounded-full bg-white/10" />

    <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8" :class="alignClass">
      <h1 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">
        {{ title }}
      </h1>
      <p class="mt-4 max-w-2xl text-lg text-c-primary-light sm:text-xl" :class="heroTextAlign === 'center' ? 'mx-auto' : heroTextAlign === 'right' ? 'ml-auto' : ''">
        {{ subtitle }}
      </p>
      <div v-if="jobCount > 0" class="mt-6">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-5 py-2 text-sm font-medium text-white backdrop-blur-sm">
          <span class="flex h-2 w-2 rounded-full bg-green-400" />
          {{ jobCount }} open {{ jobCount === 1 ? 'position' : 'positions' }}
        </span>
      </div>
    </div>
  </section>
</template>
