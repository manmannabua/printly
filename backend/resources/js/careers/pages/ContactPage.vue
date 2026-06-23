<script setup lang="ts">
import { ref } from 'vue'
import HeroSection from '../components/HeroSection.vue'
import HoneypotField from '../components/HoneypotField.vue'
import careersApi from '../services/careersApi'
import { careersUrl } from '../utils/careersUrl'
import type { SiteSettingsData } from '../types/careers'

const props = defineProps<{
  siteSettings?: SiteSettingsData
}>()

const companyName = document.getElementById('careers-app')?.dataset.company ?? ''

const name = ref('')
const email = ref('')
const subject = ref('')
const message = ref('')
const honeypot = ref('')
const submitting = ref(false)
const submitted = ref(false)
const errors = ref<Record<string, string[]>>({})
const errorMessage = ref('')

async function handleSubmit() {
  errors.value = {}
  errorMessage.value = ''
  submitting.value = true

  try {
    await careersApi.post('/contact', {
      name: name.value,
      email: email.value,
      subject: subject.value,
      message: message.value,
      website: honeypot.value,
    })
    submitted.value = true
  } catch (err: unknown) {
    const axiosErr = err as { response?: { status?: number; data?: { errors?: Record<string, string[]>; message?: string } } }
    if (axiosErr.response?.status === 422) {
      errors.value = axiosErr.response.data?.errors ?? {}
    } else if (axiosErr.response?.status === 429) {
      errorMessage.value = 'Too many requests. Please try again later.'
    } else {
      errorMessage.value = 'Something went wrong. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

function fieldError(field: string): string | undefined {
  return errors.value[field]?.[0]
}

const confirmationMessage = props.siteSettings?.contact?.contact_form_confirmation_message
</script>

<template>
  <div>
    <HeroSection
      :company-name="companyName"
      :job-count="0"
      hero-title="Contact Us"
      :hero-subtitle="siteSettings?.contact?.address || 'We\'d love to hear from you'"
      :hero-text-align="siteSettings?.branding?.hero_text_align ?? 'center'"
    />

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Success state -->
      <div v-if="submitted" class="mx-auto max-w-xl py-8 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-zinc-700">
          <svg class="h-8 w-8 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">Message Sent!</h2>
        <p class="mb-8 text-gray-600 dark:text-gray-300">
          {{ confirmationMessage || "Thank you for reaching out. We'll get back to you as soon as possible." }}
        </p>
        <a :href="careersUrl()" class="inline-flex items-center rounded-lg bg-c-primary px-4 py-2 text-white transition-colors hover:bg-c-primary-hover">
          Back to Careers
        </a>
      </div>

      <!-- Contact form + info -->
      <div v-else class="grid gap-8 lg:grid-cols-5">
        <!-- Left column: Contact info + map -->
        <div class="space-y-6 lg:col-span-2">
          <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Get in Touch</h2>
            <div class="space-y-4">
              <div v-if="siteSettings?.contact?.email" class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Email</p>
                  <a :href="'mailto:' + siteSettings.contact.email" class="text-sm text-c-primary hover:underline">
                    {{ siteSettings.contact.email }}
                  </a>
                </div>
              </div>

              <div v-if="siteSettings?.contact?.phone" class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone</p>
                  <a :href="'tel:' + siteSettings.contact.phone" class="text-sm text-c-primary hover:underline">
                    {{ siteSettings.contact.phone }}
                  </a>
                </div>
              </div>

              <div v-if="siteSettings?.contact?.address" class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-c-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Address</p>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ siteSettings.contact.address }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Map embed -->
          <div v-if="siteSettings?.contact?.map_embed_url" class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
            <iframe
              :src="siteSettings.contact.map_embed_url"
              width="100%"
              height="300"
              style="border: 0"
              allowfullscreen
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="Location map"
            />
          </div>
        </div>

        <!-- Right column: Contact form -->
        <div class="lg:col-span-3">
          <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Send a Message</h2>

            <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {{ errorMessage }}
            </div>

            <form class="space-y-4" @submit.prevent="handleSubmit">
              <HoneypotField v-model="honeypot" />

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label for="contact-name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                  <input
                    id="contact-name"
                    v-model="name"
                    type="text"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder-gray-500"
                    placeholder="Your name"
                  />
                  <p v-if="fieldError('name')" class="mt-1 text-xs text-red-600">{{ fieldError('name') }}</p>
                </div>
                <div>
                  <label for="contact-email" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                  <input
                    id="contact-email"
                    v-model="email"
                    type="email"
                    required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder-gray-500"
                    placeholder="you@example.com"
                  />
                  <p v-if="fieldError('email')" class="mt-1 text-xs text-red-600">{{ fieldError('email') }}</p>
                </div>
              </div>

              <div>
                <label for="contact-subject" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subject <span class="text-red-500">*</span></label>
                <input
                  id="contact-subject"
                  v-model="subject"
                  type="text"
                  required
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder-gray-500"
                  placeholder="What is this about?"
                />
                <p v-if="fieldError('subject')" class="mt-1 text-xs text-red-600">{{ fieldError('subject') }}</p>
              </div>

              <div>
                <label for="contact-message" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message <span class="text-red-500">*</span></label>
                <textarea
                  id="contact-message"
                  v-model="message"
                  required
                  rows="5"
                  maxlength="5000"
                  class="w-full resize-y rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-c-primary focus:outline-none focus:ring-1 focus:ring-c-primary dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:placeholder-gray-500"
                  placeholder="Your message..."
                />
                <div class="mt-1 flex items-center justify-between">
                  <p v-if="fieldError('message')" class="text-xs text-red-600">{{ fieldError('message') }}</p>
                  <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">{{ message.length }}/5000</span>
                </div>
              </div>

              <div class="pt-2">
                <button
                  type="submit"
                  :disabled="submitting"
                  class="inline-flex w-full items-center justify-center rounded-lg bg-c-primary px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-c-primary-hover disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                >
                  <svg v-if="submitting" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ submitting ? 'Sending...' : 'Send Message' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
