<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { getValidationErrors, getErrorMessage } from '@/services/api'
import * as authService from '@/services/authService'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const email = ref((route.query.email as string) || '')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const token = (route.query.token as string) || ''

async function handleSubmit(): Promise<void> {
  errors.value = {}
  loading.value = true

  try {
    const message = await authService.resetPassword({
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      token,
    })
    toast.success(message)
    await router.push({ name: 'login' })
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
      Reset your password
    </h2>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400">
      Enter your new password below.
    </p>

    <form @submit.prevent="handleSubmit" class="space-y-5">
      <!-- Email -->
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
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500"
            :class="{ 'border-red-500 dark:border-red-400': errors.email }"
          />
        </div>
        <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.email[0] }}
        </p>
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
          New password
        </label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <AppIcon name="lock" :size="18" class="text-gray-400" />
          </div>
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="new-password"
            placeholder="Minimum 8 characters"
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500"
            :class="{ 'border-red-500 dark:border-red-400': errors.password }"
          />
          <button
            type="button"
            tabindex="-1"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            @click="showPassword = !showPassword"
          >
            <AppIcon :name="showPassword ? 'eye' : 'eye-off'" :size="18" />
          </button>
        </div>
        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ errors.password[0] }}
        </p>
      </div>

      <!-- Confirm password -->
      <div>
        <label for="password-confirm" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Confirm new password
        </label>
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <AppIcon name="lock" :size="18" class="text-gray-400" />
          </div>
          <input
            id="password-confirm"
            v-model="passwordConfirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            required
            autocomplete="new-password"
            placeholder="Confirm your password"
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500"
          />
          <button
            type="button"
            tabindex="-1"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            @click="showPasswordConfirmation = !showPasswordConfirmation"
          >
            <AppIcon :name="showPasswordConfirmation ? 'eye-off' : 'eye'" :size="18" />
          </button>
        </div>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="flex w-full items-center justify-center rounded-lg bg-c-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-c-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <AppSpinner v-if="loading" size="sm" class="mr-2" />
        {{ loading ? 'Resetting...' : 'Reset password' }}
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
