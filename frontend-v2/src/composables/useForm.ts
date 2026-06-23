import { reactive, ref, computed, nextTick } from 'vue'
import { isApiValidationError, getValidationErrors, getErrorMessage } from '@/services/api'

export interface UseFormReturn<T extends Record<string, unknown>> {
  fields: T
  errors: Record<string, string[]>
  loading: boolean
  isDirty: boolean
  submit: (apiCall: (data: T) => Promise<unknown>) => Promise<boolean>
  reset: () => void
  setErrors: (errors: Record<string, string[]>) => void
  clearErrors: (field?: string) => void
  getError: (field: string) => string | undefined
  scrollToFirstError: () => void
}

function scrollToFirstError(): void {
  nextTick(() => {
    const el = document.querySelector<HTMLElement>('[aria-invalid="true"]')
    if (!el) return
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el.focus({ preventScroll: true })
  })
}

export function useForm<T extends Record<string, unknown>>(initialValues: T): UseFormReturn<T> {
  const frozen = JSON.parse(JSON.stringify(initialValues)) as T
  const fields = reactive({ ...initialValues }) as T
  const errors = ref<Record<string, string[]>>({})
  const loading = ref(false)

  const isDirty = computed(() => {
    for (const key of Object.keys(frozen)) {
      if (JSON.stringify(fields[key]) !== JSON.stringify(frozen[key as keyof T])) {
        return true
      }
    }
    return false
  })

  async function submit(apiCall: (data: T) => Promise<unknown>): Promise<boolean> {
    errors.value = {}
    loading.value = true

    try {
      await apiCall({ ...fields })
      return true
    } catch (error: unknown) {
      if (isApiValidationError(error)) {
        errors.value = getValidationErrors(error)
      } else {
        errors.value = { _form: [getErrorMessage(error)] }
      }
      scrollToFirstError()
      return false
    } finally {
      loading.value = false
    }
  }

  function reset(): void {
    const fresh = JSON.parse(JSON.stringify(frozen)) as T
    for (const key of Object.keys(fresh)) {
      (fields as Record<string, unknown>)[key] = fresh[key as keyof T]
    }
    errors.value = {}
  }

  function setErrors(newErrors: Record<string, string[]>): void {
    errors.value = newErrors
  }

  function clearErrors(field?: string): void {
    if (field) {
      const updated = { ...errors.value }
      delete updated[field]
      errors.value = updated
    } else {
      errors.value = {}
    }
  }

  function getError(field: string): string | undefined {
    return errors.value[field]?.[0]
  }

  return {
    fields,
    get errors() { return errors.value },
    get loading() { return loading.value },
    get isDirty() { return isDirty.value },
    submit,
    reset,
    setErrors,
    clearErrors,
    getError,
    scrollToFirstError,
  }
}
