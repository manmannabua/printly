<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import AppIcon from '@/components/common/AppIcon.vue'
import AppSpinner from '@/components/common/AppSpinner.vue'
import { formatMoney } from '@/utils/money'

const props = defineProps<{
  totalCents: number
  payOnPickupAllowed: boolean
  acceptsOnlinePayments: boolean
  placing: boolean
}>()

const emit = defineEmits<{
  back: []
  submit: [payload: { customer: { name?: string, phone?: string, email?: string }, pay_method: string, notes?: string }]
}>()

const customer = reactive({ name: '', phone: '', email: '' })
const paymentOptions = computed(() => [
  ...(props.payOnPickupAllowed ? [{ value: 'cash_on_pickup', label: 'Pay at pickup', icon: 'store' }] : []),
  ...(props.acceptsOnlinePayments ? [{ value: 'gcash', label: 'GCash / online', icon: 'credit-card' }] : []),
])
const payMethod = ref(paymentOptions.value[0]?.value ?? '')
const notes = ref('')
const hasContact = computed(() => Boolean(customer.phone || customer.email))
const canSubmit = computed(() => !props.placing && paymentOptions.value.length > 0 && hasContact.value)

watch(paymentOptions, (options) => {
  if (!options.some(option => option.value === payMethod.value)) {
    payMethod.value = options[0]?.value ?? ''
  }
})

function submit(): void {
  if (!canSubmit.value || !payMethod.value) return

  emit('submit', {
    customer: {
      name: customer.name || undefined,
      phone: customer.phone || undefined,
      email: customer.email || undefined,
    },
    pay_method: payMethod.value,
    notes: notes.value || undefined,
  })
}
</script>

<template>
  <div class="min-h-[calc(100dvh-2rem)]">
    <header class="sticky top-0 z-10 -mx-4 -mt-4 border-b border-slate-200 bg-slate-50/95 px-4 pb-3 pt-4 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <button class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-500" @click="emit('back')">
        <AppIcon name="arrow-left" :size="17" /> Back to cart
      </button>
      <h2 class="font-display text-2xl font-bold text-slate-950 dark:text-white">Checkout</h2>
      <p class="mt-1 text-sm text-slate-500">Add contact details so the store can update you.</p>
    </header>

    <form class="mt-4 space-y-5" @submit.prevent="submit">
      <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-400">Contact</h3>
        <div class="mt-3 space-y-3">
          <div>
            <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Name</label>
            <input v-model="customer.name" type="text" autocomplete="name" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-950">
          </div>
          <div>
            <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Phone</label>
            <input v-model="customer.phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="0917..." class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-950">
          </div>
          <div>
            <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Email <span class="font-normal text-slate-400">(optional)</span></label>
            <input v-model="customer.email" type="email" autocomplete="email" class="mt-1 h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 text-base dark:border-zinc-700 dark:bg-zinc-950">
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-400">Payment</h3>
        <div v-if="paymentOptions.length" class="mt-3 grid gap-2">
          <button
            v-for="option in paymentOptions"
            :key="option.value"
            type="button"
            class="flex min-h-14 items-center gap-3 rounded-2xl border px-4 text-left transition"
            :class="payMethod === option.value
              ? 'border-blue-600 bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-200'
              : 'border-slate-200 bg-white text-slate-600 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300'"
            @click="payMethod = option.value"
          >
            <AppIcon :name="option.icon" :size="20" />
            <span class="font-semibold">{{ option.label }}</span>
            <AppIcon v-if="payMethod === option.value" name="check" :size="18" class="ml-auto" />
          </button>
        </div>
        <div v-else class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
          This store is not accepting payments right now.
        </div>
      </section>

      <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <label class="text-sm font-semibold text-slate-700 dark:text-zinc-300">Notes <span class="font-normal text-slate-400">(optional)</span></label>
        <textarea v-model="notes" rows="3" class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base dark:border-zinc-700 dark:bg-zinc-950"></textarea>
      </section>
    </form>

    <div class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[480px] border-t border-slate-200 bg-white/95 px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom))] pt-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
      <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-medium text-slate-500">Total</span>
        <span class="text-xl font-bold text-slate-950 dark:text-white">{{ formatMoney(totalCents) }}</span>
      </div>
      <button
        class="w-full rounded-2xl bg-blue-600 py-3 font-semibold text-white shadow-xl shadow-blue-200 disabled:opacity-50 dark:shadow-blue-950/40"
        :disabled="!canSubmit"
        @click="submit"
      >
        <AppSpinner v-if="placing" size="sm" />
        <span v-else>Place order</span>
      </button>
      <p v-if="!hasContact" class="mt-2 text-center text-xs text-slate-400">Phone or email is required.</p>
    </div>
  </div>
</template>
