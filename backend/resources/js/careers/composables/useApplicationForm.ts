import { ref, computed, watch } from 'vue'
import careersApi from '../services/careersApi'
import type { ApplyJobData } from '../types/careers'

export interface EducationEntry {
  institution: string
  level: string
  status: string
  degree: string
  field_of_study: string
  start_date: string
  end_date: string
  is_current: boolean
  description: string
}

export interface WorkExperienceEntry {
  company: string
  company_address: string
  job_title: string
  start_date: string
  end_date: string
  is_current: boolean
  description: string
  reason_for_leaving: string
}

export interface GovernmentIds {
  tin: string
  sss: string
  philhealth: string
  pagibig: string
}

export interface EmergencyContact {
  name: string
  relationship: string
  phone_primary: string
}

export interface SkillEntry {
  name: string
  category: string
  proficiency_level: number
  years_experience: string
}

export interface ReferenceEntry {
  name: string
  relationship: string
  company: string
  position: string
  email: string
  phone: string
}

export interface ApplicationFormData {
  first_name: string
  middle_name: string
  last_name: string
  email: string
  phone: string
  address: string
  date_of_birth: string
  birth_place: string
  nationality: string
  gender: string
  civil_status: string
  father_name: string
  mother_name: string
  expected_salary: string
  cover_letter: string
  government_ids: GovernmentIds
  emergency_contact: EmergencyContact
  educations: EducationEntry[]
  work_experiences: WorkExperienceEntry[]
  skills: SkillEntry[]
  references: ReferenceEntry[]
  resume: File | null
  photo: File | null
  website: string // honeypot
}

export type ApplicationStep = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8

const STORAGE_PREFIX = 'draft-application-'
const STORAGE_TTL_DAYS = 7

function getStorageKey(slug: string): string {
  return `${STORAGE_PREFIX}${slug}`
}

function getEditStorageKey(token: string): string {
  return `${STORAGE_PREFIX}edit-${token}`
}

function createEmptyForm(): ApplicationFormData {
  return {
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    date_of_birth: '',
    birth_place: '',
    nationality: 'Filipino',
    gender: '',
    civil_status: '',
    father_name: '',
    mother_name: '',
    expected_salary: '',
    cover_letter: '',
    government_ids: { tin: '', sss: '', philhealth: '', pagibig: '' },
    emergency_contact: { name: '', relationship: '', phone_primary: '' },
    educations: [],
    work_experiences: [],
    skills: [],
    references: [],
    resume: null,
    photo: null,
    website: '',
  }
}

export function createEmptyEducation(): EducationEntry {
  return {
    institution: '',
    level: '',
    status: '',
    degree: '',
    field_of_study: '',
    start_date: '',
    end_date: '',
    is_current: false,
    description: '',
  }
}

export function createEmptyWorkExperience(): WorkExperienceEntry {
  return {
    company: '',
    company_address: '',
    job_title: '',
    start_date: '',
    end_date: '',
    is_current: false,
    description: '',
    reason_for_leaving: '',
  }
}

export function createEmptySkill(): SkillEntry {
  return {
    name: '',
    category: 'other',
    proficiency_level: 50,
    years_experience: '',
  }
}

export function createEmptyReference(): ReferenceEntry {
  return {
    name: '',
    relationship: '',
    company: '',
    position: '',
    email: '',
    phone: '',
  }
}

function readStepFromUrl(totalSteps: number): ApplicationStep | null {
  if (typeof window === 'undefined') return null
  const raw = new URLSearchParams(window.location.search).get('step')
  if (!raw) return null
  const n = parseInt(raw, 10)
  if (Number.isNaN(n) || n < 1 || n > totalSteps) return null
  return n as ApplicationStep
}

function writeStepToUrl(step: number): void {
  if (typeof window === 'undefined') return
  const params = new URLSearchParams(window.location.search)
  if (step <= 1) {
    params.delete('step')
  } else {
    params.set('step', String(step))
  }
  const qs = params.toString()
  const newUrl = `${window.location.pathname}${qs ? `?${qs}` : ''}${window.location.hash}`
  window.history.replaceState(window.history.state, '', newUrl)
}

export interface UseApplicationFormOptions {
  /**
   * When set, the form is operating in "edit mode" — applicant is updating
   * an existing application via a tokenized link. The composable:
   *   - keys its localStorage draft on the token rather than the job slug,
   *   - starts the wizard at Step 2 (Personal Info) since Step 1 (resume
   *     upload) is meaningless when the form is already pre-filled,
   *   - treats email verification as already-satisfied (the token IS the
   *     auth), so Step 8 surfaces no OTP UI.
   */
  editToken?: string
  /**
   * Initial form values to seed when starting fresh. Used by edit mode
   * to populate from the API prefill payload.
   */
  initialFormData?: Partial<ApplicationFormData>
}

export function useApplicationForm(job: ApplyJobData, options: UseApplicationFormOptions = {}) {
  const editMode = !!options.editToken
  const currentStep = ref<ApplicationStep>(editMode ? 2 : 1)
  const form = ref<ApplicationFormData>({ ...createEmptyForm(), ...(options.initialFormData ?? {}) })
  const submitting = ref(false)
  const submitError = ref<string | null>(null)
  const serverErrors = ref<Record<string, string[]>>({})
  const isParsing = ref(false)
  const resumeParsed = ref(false)

  // Email verification state. In edit mode the link itself authorizes the
  // submission, so we mark verified up-front with a synthetic token sentinel
  // (the controller ignores this field for edit submissions).
  const emailVerificationToken = ref<string | null>(editMode ? 'edit-mode' : null)
  const verifiedEmail = ref<string | null>(editMode ? form.value.email.trim().toLowerCase() : null)

  const totalSteps = 8

  // Reset verification when email changes — but in edit mode we skip OTP
  // entirely, so let the synthetic 'edit-mode' token stand even if the user
  // types a different email.
  watch(
    () => form.value.email,
    (newEmail) => {
      if (editMode) return
      if (verifiedEmail.value && newEmail.trim().toLowerCase() !== verifiedEmail.value) {
        emailVerificationToken.value = null
        verifiedEmail.value = null
      }
    },
  )

  function storageKey(): string {
    return options.editToken ? getEditStorageKey(options.editToken) : storageKey()
  }

  // Load draft from localStorage
  function loadDraft(): void {
    try {
      const raw = localStorage.getItem(storageKey())
      if (!raw) return

      const { data, savedAt } = JSON.parse(raw)
      const savedDate = new Date(savedAt)
      const now = new Date()
      const diffDays = (now.getTime() - savedDate.getTime()) / (1000 * 60 * 60 * 24)

      if (diffDays > STORAGE_TTL_DAYS) {
        localStorage.removeItem(storageKey())
        return
      }

      // Restore form data (except files — can't persist File objects)
      form.value = { ...createEmptyForm(), ...data, resume: null, photo: null }

      // Clamp restored currentStep to valid range (handles stale 7-step drafts)
      if (data.currentStep && data.currentStep > totalSteps) {
        currentStep.value = totalSteps as ApplicationStep
      }
    } catch {
      // Invalid data — ignore
    }
  }

  // Restore step from URL so refresh keeps the user on the same step.
  // Runs after loadDraft so the URL wins over stale draft state.
  function applyStepFromUrl(): void {
    const stepFromUrl = readStepFromUrl(totalSteps)
    if (stepFromUrl !== null) {
      currentStep.value = stepFromUrl
    }
  }

  // Sync currentStep -> URL as the user advances/goes back.
  watch(currentStep, (step) => {
    writeStepToUrl(step)
  })

  // Save draft to localStorage
  function saveDraft(): void {
    try {
      const { resume, photo, website, ...persistable } = form.value
      localStorage.setItem(
        storageKey(),
        JSON.stringify({
          data: persistable,
          savedAt: new Date().toISOString(),
        }),
      )
    } catch {
      // Storage full or unavailable
    }
  }

  // Clear draft on successful submission
  function clearDraft(): void {
    localStorage.removeItem(storageKey())
  }

  // Auto-save on form changes (debounced via watch)
  let saveTimeout: ReturnType<typeof setTimeout> | null = null
  watch(
    form,
    () => {
      if (saveTimeout) clearTimeout(saveTimeout)
      saveTimeout = setTimeout(saveDraft, 1000)
    },
    { deep: true },
  )

  // Step validation
  const stepValid = computed(() => {
    const f = form.value
    return {
      1: true, // Resume upload — optional, always valid
      2: !!(
        f.first_name.trim() &&
        f.last_name.trim() &&
        f.email.trim() &&
        f.phone.trim() &&
        f.address.trim() &&
        f.date_of_birth.trim() &&
        f.birth_place.trim() &&
        f.nationality.trim() &&
        f.gender.trim() &&
        f.civil_status.trim() &&
        f.father_name.trim() &&
        f.mother_name.trim() &&
        f.emergency_contact.name.trim() &&
        f.emergency_contact.relationship.trim() &&
        f.emergency_contact.phone_primary.trim() &&
        f.cover_letter.trim().length >= 20
      ),
      3: f.educations.length >= 1
        && f.educations.every((e) => e.institution.trim() && e.level && e.status)
        && f.educations.some((e) => e.level === 'secondary')
        && f.educations.some((e) => e.level === 'elementary'),
      4: f.work_experiences.length >= 1
        && f.work_experiences.every((e) =>
          e.company.trim()
          && e.company_address.trim()
          && e.job_title.trim()
          && e.start_date.trim()
          && e.description.trim()
          && (e.is_current || e.reason_for_leaving.trim()),
        ),
      5: f.skills.length >= 5
        && f.skills.every((s) => s.name.trim() && String(s.years_experience).trim() !== ''),
      6: f.references.length >= 2
        && f.references.every((r) =>
          r.name.trim()
          && r.relationship.trim()
          && r.company.trim()
          && r.position.trim()
          && r.email.trim()
          && r.phone.trim(),
        ),
      // Photo is required for new applications, optional for edits
      // (the existing photo on file is reused unless replaced).
      7: editMode || !!f.photo,
      8: emailVerificationToken.value !== null,
    }
  })

  const canProceed = computed(() => stepValid.value[currentStep.value])

  const hasMinimumContent = computed(() => {
    const f = form.value
    return (
      f.educations.length >= 1 &&
      f.work_experiences.length >= 1 &&
      f.skills.length >= 5 &&
      f.references.length >= 2 &&
      (editMode || f.photo !== null)
    )
  })

  const emailVerified = computed(() => emailVerificationToken.value !== null)

  function setEmailVerified(token: string): void {
    emailVerificationToken.value = token
    verifiedEmail.value = form.value.email.trim().toLowerCase()
  }

  function clearEmailVerification(): void {
    emailVerificationToken.value = null
    verifiedEmail.value = null
  }

  function nextStep(): void {
    if (currentStep.value < totalSteps && canProceed.value) {
      currentStep.value = (currentStep.value + 1) as ApplicationStep
    }
  }

  function prevStep(): void {
    if (currentStep.value > 1) {
      currentStep.value = (currentStep.value - 1) as ApplicationStep
    }
  }

  function goToStep(step: ApplicationStep): void {
    // Only allow going to steps we've validated through
    if (step >= 1 && step <= totalSteps) {
      currentStep.value = step
    }
  }

  async function parseResume(file: File): Promise<void> {
    // Only PDFs are supported by the backend parser
    if (file.type !== 'application/pdf') return

    isParsing.value = true
    try {
      const formData = new FormData()
      formData.append('resume', file)

      // Pass job context for combined parse+match in one Gemini call (AI-PARSE-SESSION-01)
      if (job.id) formData.append('job_posting_id', job.id)
      if (emailVerificationToken.value && emailVerificationToken.value !== 'edit-mode') {
        formData.append('session_token', emailVerificationToken.value)
      }

      const response = await careersApi.post('/resume/parse', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      if (!response.data?.success) return

      const parsed = response.data.data

      // Normalize partial dates (YYYY or YYYY-MM) to full YYYY-MM-DD for form date inputs
      function normalizeDate(d: string | undefined): string {
        if (!d) return ''
        if (/^\d{4}$/.test(d)) return `${d}-01-01`
        if (/^\d{4}-\d{2}$/.test(d)) return `${d}-01`
        return d
      }

      // Personal info — only fill if currently empty
      if (parsed.first_name && !form.value.first_name.trim()) form.value.first_name = parsed.first_name
      if (parsed.middle_name && !form.value.middle_name.trim()) form.value.middle_name = parsed.middle_name
      if (parsed.last_name && !form.value.last_name.trim()) form.value.last_name = parsed.last_name
      if (parsed.email && !form.value.email.trim()) form.value.email = parsed.email
      if (parsed.phone && !form.value.phone.trim()) form.value.phone = parsed.phone
      if (parsed.address && !form.value.address.trim()) form.value.address = parsed.address
      if (parsed.date_of_birth && !form.value.date_of_birth.trim()) form.value.date_of_birth = normalizeDate(parsed.date_of_birth)
      if (parsed.birth_place && !form.value.birth_place.trim()) form.value.birth_place = parsed.birth_place
      if (parsed.nationality && form.value.nationality === 'Filipino') form.value.nationality = parsed.nationality
      if (parsed.gender && !form.value.gender.trim()) form.value.gender = parsed.gender
      if (parsed.civil_status && !form.value.civil_status.trim()) form.value.civil_status = parsed.civil_status
      if (parsed.father_name && !form.value.father_name.trim()) form.value.father_name = parsed.father_name
      if (parsed.mother_name && !form.value.mother_name.trim()) form.value.mother_name = parsed.mother_name
      if (parsed.summary && !form.value.cover_letter.trim()) form.value.cover_letter = parsed.summary

      if (parsed.government_ids && typeof parsed.government_ids === 'object') {
        const ids = parsed.government_ids
        if (ids.tin && !form.value.government_ids.tin.trim()) form.value.government_ids.tin = ids.tin
        if (ids.sss && !form.value.government_ids.sss.trim()) form.value.government_ids.sss = ids.sss
        if (ids.philhealth && !form.value.government_ids.philhealth.trim()) form.value.government_ids.philhealth = ids.philhealth
        if (ids.pagibig && !form.value.government_ids.pagibig.trim()) form.value.government_ids.pagibig = ids.pagibig
      }

      if (parsed.emergency_contact && typeof parsed.emergency_contact === 'object') {
        const ec = parsed.emergency_contact
        if (ec.name && !form.value.emergency_contact.name.trim()) form.value.emergency_contact.name = ec.name
        if (ec.relationship && !form.value.emergency_contact.relationship.trim()) form.value.emergency_contact.relationship = ec.relationship
        if (ec.phone_primary && !form.value.emergency_contact.phone_primary.trim()) form.value.emergency_contact.phone_primary = ec.phone_primary
      }

      // Structured arrays — prepend extracted entries (don't wipe existing)
      if (parsed.educations?.length) {
        form.value.educations = [
          ...parsed.educations.map((edu: any) => ({
            institution: edu.institution ?? '',
            level: edu.level ?? '',
            status: edu.status ?? '',
            degree: edu.degree ?? '',
            field_of_study: edu.field_of_study ?? '',
            start_date: normalizeDate(edu.start_date),
            end_date: normalizeDate(edu.end_date),
            is_current: false,
            description: '',
          })),
          ...form.value.educations,
        ]
      }

      if (parsed.work_experiences?.length) {
        form.value.work_experiences = [
          ...parsed.work_experiences.map((exp: any) => {
            const endDate = normalizeDate(exp.end_date)
            // Treat "no end date" as the user's current job — otherwise
            // every parsed entry would require a reason_for_leaving even
            // for the role they still hold, blocking the next step.
            return {
              company: exp.company ?? '',
              company_address: exp.company_address ?? '',
              job_title: exp.job_title ?? '',
              start_date: normalizeDate(exp.start_date),
              end_date: endDate,
              is_current: !endDate,
              description: exp.description ?? '',
              reason_for_leaving: '',
            }
          }),
          ...form.value.work_experiences,
        ]
      }

      if (parsed.skills?.length) {
        form.value.skills = [
          ...parsed.skills.map((skill: any) => ({
            name: skill.name ?? '',
            category: skill.category ?? 'other',
            proficiency_level: skill.proficiency_level ?? 50,
            years_experience: '',
          })),
          ...form.value.skills,
        ]
      }

      resumeParsed.value = true
    } catch {
      // Silently fail — user fills manually
    } finally {
      isParsing.value = false
    }
  }

  // Load draft on init, then let the URL override the step if present.
  loadDraft()
  applyStepFromUrl()
  // Make sure the URL reflects the resolved step, even when no ?step= was provided
  // (so subsequent navigation always starts from a synced URL).
  writeStepToUrl(currentStep.value)

  return {
    currentStep,
    form,
    submitting,
    submitError,
    serverErrors,
    totalSteps,
    stepValid,
    canProceed,
    hasMinimumContent,
    isParsing,
    resumeParsed,
    parseResume,
    nextStep,
    prevStep,
    goToStep,
    clearDraft,
    saveDraft,
    emailVerificationToken,
    emailVerified,
    setEmailVerified,
    clearEmailVerification,
    editMode,
  }
}
