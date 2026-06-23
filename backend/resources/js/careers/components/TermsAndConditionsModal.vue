<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    open: boolean
    companyName?: string
  }>(),
  {
    companyName: 'the Company',
  },
)

const emit = defineEmits<{
  (e: 'close'): void
}>()

function handleKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape' && props.open) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})

watch(
  () => props.open,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
  },
)
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="terms-modal-title"
        @click.self="emit('close')"
      >
        <div
          class="flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-zinc-900"
        >
          <!-- Header -->
          <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
            <div>
              <h2 id="terms-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                Terms and Conditions &amp; Data Privacy Notice
              </h2>
              <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                Pursuant to Republic Act No. 10173 (Data Privacy Act of 2012)
              </p>
            </div>
            <button
              type="button"
              class="ml-4 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-zinc-800 dark:hover:text-gray-200"
              aria-label="Close"
              @click="emit('close')"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto px-6 py-5 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
            <p class="mb-4">
              Before proceeding with your application, please read the following carefully. Clicking
              <span class="font-medium">&ldquo;I agree&rdquo;</span> confirms that you have read,
              understood, and freely given your consent to the terms below. {{ companyName }} (the
              <span class="font-medium">&ldquo;Personal Information Controller&rdquo;</span> or
              <span class="font-medium">&ldquo;PIC&rdquo;</span>) collects and processes your personal
              information in accordance with Republic Act No. 10173, the
              <span class="italic">Data Privacy Act of 2012</span>, its Implementing Rules and
              Regulations, and the issuances of the National Privacy Commission (NPC).
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              1. Personal Information We Collect
            </h3>
            <p class="mb-2">In connection with your job application, we collect:</p>
            <ul class="mb-3 list-disc space-y-1 pl-5">
              <li>
                <span class="font-medium">Identifying information</span> &mdash; full name, date and
                place of birth, nationality, gender, civil status, parents' names, contact details,
                and current address.
              </li>
              <li>
                <span class="font-medium">Sensitive personal information</span> &mdash; government-issued
                identifiers (TIN, SSS, PhilHealth, Pag-IBIG/HDMF) where you choose to provide them,
                and your photograph.
              </li>
              <li>
                <span class="font-medium">Professional information</span> &mdash; educational background,
                employment history, skills, expected compensation, cover letter, and resume.
              </li>
              <li>
                <span class="font-medium">Third-party information</span> &mdash; emergency contact
                details and professional references that you supply. You confirm you have the
                authority to share their information with us for these purposes.
              </li>
              <li>
                <span class="font-medium">Technical information</span> &mdash; IP address, browser
                user agent, and submission timestamp, collected automatically for fraud prevention
                and audit logging.
              </li>
            </ul>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              2. Purpose of Processing
            </h3>
            <p class="mb-2">We process your personal data only for the following declared purposes:</p>
            <ul class="mb-3 list-disc space-y-1 pl-5">
              <li>Evaluating your qualifications and suitability for the position you applied for;</li>
              <li>Communicating with you about your application, interviews, and assessments;</li>
              <li>Verifying the accuracy of the information provided, including reference checks;</li>
              <li>Maintaining a talent pool for future, related vacancies (only with your consent);</li>
              <li>Complying with legal, regulatory, and audit obligations; and</li>
              <li>
                If you are hired, transferring your data to your employee record to facilitate
                onboarding, payroll, statutory reporting, and other employment-related functions.
              </li>
            </ul>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              3. Lawful Basis
            </h3>
            <p class="mb-3">
              We rely on (a) your <span class="font-medium">consent</span> given through this form;
              (b) the <span class="font-medium">necessity to enter into a contract</span> at your
              request (i.e., a potential employment contract); and (c) our
              <span class="font-medium">legitimate interest</span> in evaluating candidates fairly
              and securing our recruitment process, balanced against your fundamental rights.
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              4. Sharing and Disclosure
            </h3>
            <p class="mb-3">
              Your information is accessed only by authorized personnel of {{ companyName }} who
              are involved in the recruitment decision (e.g., HR, hiring managers, and
              interviewers), and by service providers acting under written confidentiality and
              data-processing agreements (such as our IT and email infrastructure providers). We do
              <span class="font-medium">not</span> sell your personal information. We disclose data
              to government agencies only when required by law, lawful order, or subpoena.
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              5. Storage, Security, and Retention
            </h3>
            <p class="mb-3">
              Your data is stored on access-controlled systems with reasonable organizational,
              physical, and technical safeguards consistent with NPC Circular No. 16-01. Sensitive
              fields (e.g., date of birth, address, parents' names, expected salary) are encrypted
              at rest. We retain application records for the duration of the recruitment cycle and
              up to <span class="font-medium">two (2) years</span> thereafter for audit, defense of
              legal claims, and consideration for related future openings, after which records are
              securely disposed of. If you are hired, your data is migrated to your employee file
              and retained per our employment records policy and applicable labor laws.
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              6. Your Rights as a Data Subject
            </h3>
            <p class="mb-2">Under the Data Privacy Act, you have the right to:</p>
            <ul class="mb-3 list-disc space-y-1 pl-5">
              <li>Be informed about how your personal data is processed;</li>
              <li>Access a copy of your personal data we hold;</li>
              <li>
                Object to processing, or rectify inaccurate or outdated information;
              </li>
              <li>Erase or block your data when grounds under the law are present;</li>
              <li>Data portability in a commonly used, structured electronic format;</li>
              <li>
                <span class="font-medium">Withdraw your consent at any time</span>, without
                affecting the lawfulness of processing already performed; and
              </li>
              <li>
                Lodge a complaint with the National Privacy Commission
                (<a href="https://privacy.gov.ph" target="_blank" rel="noopener noreferrer" class="text-c-primary hover:underline">privacy.gov.ph</a>)
                if you believe your rights have been violated.
              </li>
            </ul>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              7. Data Protection Officer
            </h3>
            <p class="mb-3">
              To exercise any of the rights above, withdraw consent, or raise privacy concerns,
              please contact our Data Protection Officer through the contact channel published on
              our careers website. We will respond within the timeframes prescribed by the National
              Privacy Commission.
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              8. Accuracy of Information
            </h3>
            <p class="mb-3">
              You warrant that the information you submit is true, accurate, and complete to the
              best of your knowledge. Any material misrepresentation may be ground for
              disqualification from the recruitment process or, if discovered later, termination of
              employment, without prejudice to other remedies available under the law.
            </p>

            <h3 class="mt-5 mb-2 text-sm font-semibold text-gray-900 dark:text-white">
              9. Consent
            </h3>
            <p class="mb-1">
              By ticking the <span class="font-medium">&ldquo;I agree&rdquo;</span> checkbox and
              submitting this application, you confirm that you have read this notice in full, that
              your consent is freely given, specific, and informed, and that you authorize
              {{ companyName }} to collect and process your personal information for the purposes
              described above.
            </p>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-3 dark:border-zinc-700">
            <button
              type="button"
              class="inline-flex items-center rounded-md bg-c-primary px-4 py-2 text-sm font-medium text-white hover:bg-c-primary-hover"
              @click="emit('close')"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
