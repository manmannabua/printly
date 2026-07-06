<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed', platform: string }>
}

const installPrompt = ref<BeforeInstallPromptEvent | null>(null)
const dismissedInstall = ref(localStorage.getItem('printly_install_dismissed') === '1')
const canInstall = computed(() => installPrompt.value !== null && !dismissedInstall.value)

onMounted(() => {
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    installPrompt.value = event as BeforeInstallPromptEvent
  })
})

async function installApp(): Promise<void> {
  if (!installPrompt.value) return

  await installPrompt.value.prompt()
  await installPrompt.value.userChoice
  installPrompt.value = null
}

function dismissInstall(): void {
  dismissedInstall.value = true
  localStorage.setItem('printly_install_dismissed', '1')
}
</script>

<template>
  <div class="min-h-dvh bg-slate-100 text-slate-950 dark:bg-zinc-950 dark:text-white">
    <div class="mx-auto min-h-dvh w-full max-w-[480px] bg-slate-50 shadow-2xl shadow-slate-200/70 dark:bg-zinc-950 dark:shadow-black/30">
      <div
        v-if="canInstall"
        class="sticky top-0 z-40 flex items-center gap-3 border-b border-blue-100 bg-blue-600 px-4 py-3 text-white"
      >
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15">
          <AppIcon name="smartphone" :size="20" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold">Install Printly</p>
          <p class="truncate text-xs text-blue-100">Faster access to orders and pickup updates.</p>
        </div>
        <button class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-blue-700" @click="installApp">
          Install
        </button>
        <button class="p-1 text-blue-100" aria-label="Dismiss install prompt" @click="dismissInstall">
          <AppIcon name="x" :size="16" />
        </button>
      </div>

      <main class="px-4 pb-[calc(6rem+env(safe-area-inset-bottom))] pt-[calc(1rem+env(safe-area-inset-top))]">
        <router-view />
      </main>
    </div>
  </div>
</template>
