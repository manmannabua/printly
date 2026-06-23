<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { getValidationErrors, getErrorMessage } from '@/services/api'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const toast = useToast()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const showPassword = ref(false)

onMounted(() => {
  if (route.query.expired === '1') {
    toast.warning('Session expired. Please log in again.')
  }
})

async function handleSubmit(): Promise<void> {
  errors.value = {}
  loading.value = true

  try {
    await authStore.login(email.value, password.value)
    const redirect = (route.query.redirect as string) || '/dashboard'
    const safeRedirect = redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : '/dashboard'
    await router.push(safeRedirect)
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
    <p class="font-mono text-[11px] uppercase tracking-[0.3em] text-c-primary">Station access</p>
    <h2 class="mb-6 mt-2 font-display text-2xl font-bold text-gray-900 dark:text-white">
      Sign in
    </h2>

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
            placeholder="you@company.com"
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-cyan-600 focus:outline-none focus:ring-1 focus:ring-cyan-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-cyan-400 dark:focus:ring-cyan-400"
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
          Password
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
            autocomplete="current-password"
            placeholder="Enter your password"
            class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 placeholder-gray-400 focus:border-cyan-600 focus:outline-none focus:ring-1 focus:ring-cyan-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-cyan-400 dark:focus:ring-cyan-400"
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

      <!-- Forgot password link -->
      <div class="text-right">
        <router-link
          to="/forgot-password"
          class="text-sm text-c-primary hover:text-c-primary-hover"
        >
          Forgot your password?
        </router-link>
      </div>

      <!-- Submit button -->
      <button
        type="submit"
        :disabled="loading"
        class="flex w-full items-center justify-center rounded-lg bg-c-primary px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-c-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      >
        <AppSpinner v-if="loading" size="sm" class="mr-2" />
        {{ loading ? 'Signing in...' : 'Sign in' }}
      </button>
    </form>
  </div>
</template>
