<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/services/api'
import * as authService from '@/services/authService'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'

const auth = useAuthStore()
const toast = useToast()

// ── Change password ────────────────────────────────────────────────────────
const pw = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const pwError = ref<string | null>(null)
const pwSaving = ref(false)

async function submitPassword(): Promise<void> {
  pwError.value = null
  if (pw.value.new_password !== pw.value.new_password_confirmation) {
    pwError.value = 'New password confirmation does not match.'
    return
  }
  pwSaving.value = true
  try {
    const msg = await authService.changePassword(pw.value)
    toast.success(msg || 'Password changed.')
    pw.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (e) {
    pwError.value = getErrorMessage(e)
  } finally {
    pwSaving.value = false
  }
}

// ── Security PIN ───────────────────────────────────────────────────────────
const pin = ref({ pin: '', current_password: '', current_pin: '' })
const pinError = ref<string | null>(null)
const pinSaving = ref(false)

async function submitPin(): Promise<void> {
  pinError.value = null
  if (!/^\d{4}$/.test(pin.value.pin)) {
    pinError.value = 'PIN must be exactly 4 digits.'
    return
  }
  pinSaving.value = true
  try {
    const payload = auth.hasSecurityPin
      ? { pin: pin.value.pin, current_pin: pin.value.current_pin }
      : { pin: pin.value.pin, current_password: pin.value.current_password }
    const res = await authService.setSecurityPin(payload)
    if (auth.user) auth.user.has_security_pin = res.has_security_pin
    toast.success('Security PIN updated.')
    pin.value = { pin: '', current_password: '', current_pin: '' }
  } catch (e) {
    pinError.value = getErrorMessage(e)
  } finally {
    pinSaving.value = false
  }
}
</script>

<template>
  <div class="grid gap-5 lg:grid-cols-2">
    <!-- Change password -->
    <AppCard>
      <form class="space-y-3 p-4" @submit.prevent="submitPassword">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Change password</h4>
        <AppInput v-model="pw.current_password" type="password" label="Current password" autocomplete="current-password" required />
        <AppInput v-model="pw.new_password" type="password" label="New password" autocomplete="new-password" required />
        <AppInput v-model="pw.new_password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
        <p v-if="pwError" class="text-xs text-danger-600">{{ pwError }}</p>
        <AppButton type="submit" size="sm" icon="check" :loading="pwSaving">Update password</AppButton>
      </form>
    </AppCard>

    <!-- Security PIN -->
    <AppCard>
      <form class="space-y-3 p-4" @submit.prevent="submitPin">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
          {{ auth.hasSecurityPin ? 'Change security PIN' : 'Set a security PIN' }}
        </h4>
        <AppInput
          v-if="auth.hasSecurityPin"
          v-model="pin.current_pin"
          type="password"
          label="Current PIN"
          placeholder="••••"
        />
        <AppInput
          v-else
          v-model="pin.current_password"
          type="password"
          label="Current password"
          autocomplete="current-password"
        />
        <AppInput v-model="pin.pin" type="password" label="New 4-digit PIN" placeholder="••••" />
        <p v-if="pinError" class="text-xs text-danger-600">{{ pinError }}</p>
        <AppButton type="submit" size="sm" icon="check" :loading="pinSaving">Save PIN</AppButton>
      </form>
    </AppCard>
  </div>
</template>
