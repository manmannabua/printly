<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { getValidationErrors, getErrorMessage } from '@/services/api'
import * as authService from '@/services/authService'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const toast = useToast()

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const errors = ref<Record<string, string[]>>({})

async function handleSubmit(): Promise<void> {
  errors.value = {}
  loading.value = true

  try {
    const message = await authService.forgotPassword({ email: email.value })
    sent.value = true
    toast.success(message)
  } catch (error) {
    const validationErrors = getValidationErrors(error)
    if (Object.keys(validationErrors).length > 0) {
      errors.value = validationErrors
    } else {
      toast.error(getErrorMessage(error))
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h2 class="mb-2 text-center text-xl font-semibold text-gray-900 dark:text-gray-100">
      Forgot your password?
    </h2>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400">
      Enter your email and we'll send you a reset link.
    </p>

    <div v-if="sent" class="text-center">
      <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
        <AppIcon name="check-circle" :size="24" class="text-green-600 dark:text-green-400" />
      </div>
      <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        If an account exists with that email, you'll receive a password reset link shortly.
      </p>
      <router-link
        to="/login"
        class="text-sm font-medium text-c-primary hover:text-c-primary-hover"
      >
        Back to login
      </router-link>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-5">
      <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Email address
        </label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <AppIcon name="mail" :size="18" class="text-gray-400" />
          </div>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="you@company.com"
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500"
            :class="{ 'border-red-500 dark:border-red-400': errors.email }"
          />
        </div>
        <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.email[0] }}
        </p>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="flex w-full items-center justify-center rounded-lg bg-c-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-c-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <AppSpinner v-if="loading" size="sm" class="mr-2" />
        {{ loading ? 'Sending...' : 'Send reset link' }}
      </button>

      <div class="text-center">
        <router-link
          to="/login"
          class="inline-flex items-center gap-1 text-sm text-c-primary hover:text-c-primary-hover"
        >
          <AppIcon name="arrow-left" :size="14" />
          Back to login
        </router-link>
      </div>
    </form>
  </div>
</template>
