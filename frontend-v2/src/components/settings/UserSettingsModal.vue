<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDarkMode } from '@/composables/useDarkMode'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/services/api'
import * as authService from '@/services/authService'
import AppModal from '@/components/ui/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppIcon from '@/components/common/AppIcon.vue'

defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()
const { isDark, toggle: toggleDark } = useDarkMode()

const TABS = [
  { key: 'general', label: 'General', icon: 'settings' },
  { key: 'security', label: 'Security', icon: 'shield-check' },
] as const
const activeTab = ref<'general' | 'security'>('general')

function close(): void {
  emit('update:modelValue', false)
}

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

// ── Logout ─────────────────────────────────────────────────────────────────
async function handleLogout(): Promise<void> {
  await auth.logout()
  close()
  router.push({ name: 'login' })
}
</script>

<template>
  <AppModal :model-value="modelValue" title="Settings" size="lg" @update:model-value="emit('update:modelValue', $event)">
    <!-- Identity header -->
    <div class="mb-5 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
      <AppAvatar :name="auth.user?.email" size="lg" />
      <div class="min-w-0">
        <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ auth.user?.email }}</p>
        <p class="text-xs capitalize text-gray-400">{{ auth.primaryRole ?? 'User' }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-5 flex gap-1 rounded-lg border border-gray-200 p-1 dark:border-gray-700">
      <button
        v-for="tab in TABS"
        :key="tab.key"
        class="flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition"
        :class="activeTab === tab.key ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'"
        @click="activeTab = tab.key"
      >
        <AppIcon :name="tab.icon" :size="16" />
        {{ tab.label }}
      </button>
    </div>

    <!-- General -->
    <div v-if="activeTab === 'general'" class="space-y-4">
      <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
        <AppToggle
          :model-value="isDark"
          label="Dark mode"
          description="Switch between light and dark appearance."
          @update:model-value="toggleDark"
        />
      </div>
    </div>

    <!-- Security -->
    <div v-else class="space-y-6">
      <!-- Change password -->
      <form class="space-y-3" @submit.prevent="submitPassword">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Change password</h4>
        <AppInput v-model="pw.current_password" type="password" label="Current password" autocomplete="current-password" required />
        <AppInput v-model="pw.new_password" type="password" label="New password" autocomplete="new-password" required />
        <AppInput v-model="pw.new_password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
        <p v-if="pwError" class="text-xs text-danger-600">{{ pwError }}</p>
        <AppButton type="submit" size="sm" icon="check" :loading="pwSaving">Update password</AppButton>
      </form>

      <!-- Security PIN -->
      <form class="space-y-3 border-t border-gray-100 pt-5 dark:border-gray-700" @submit.prevent="submitPin">
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
    </div>

    <template #footer>
      <AppButton variant="ghost" icon="log-out" class="!text-danger-600" @click="handleLogout">
        Log out
      </AppButton>
      <AppButton variant="secondary" @click="close">Close</AppButton>
    </template>
  </AppModal>
</template>
