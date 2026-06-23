import { ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import type { PaginationMeta } from '@/types/api'

export interface UseApiListOptions {
  defaultSort?: { field: string; dir: 'asc' | 'desc' }
  defaultPerPage?: number
  /** Dynamic filters — user-settable via setFilter(), synced to URL when syncUrl is true */
  filters?: Record<string, unknown>
  /** Static filters — always applied to API params, never synced to URL (e.g. { status: 'terminated' }) */
  staticFilters?: Record<string, unknown>
  immediate?: boolean
  syncUrl?: boolean
}

export interface UseApiListReturn<T> {
  data: T[]
  meta: PaginationMeta | null
  loading: boolean
  initialLoading: boolean
  search: string
  sortBy: string
  sortDir: 'asc' | 'desc'
  filters: Record<string, unknown>
  currentPage: number
  perPage: number
  paginationProps: { currentPage: number; lastPage: number; total: number; from: number; to: number; perPage: number } | undefined
  fetch: () => Promise<void>
  setSort: (field: string) => void
  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  setFilter: (key: string, value: unknown) => void
  refresh: () => Promise<void>
  createFilterHandler: (key: string) => (value: string | number | null) => void
}

function parsePositiveInt(val: unknown, fallback: number): number {
  const num = Number(val)
  return Number.isFinite(num) && num >= 1 ? Math.floor(num) : fallback
}

const RESERVED_URL_KEYS = new Set(['page', 'per_page', 'search', 'sort_by', 'sort_dir'])

export function useApiList<T>(endpoint: string, options?: UseApiListOptions): UseApiListReturn<T> {
  const defaultPerPage = options?.defaultPerPage ?? 15
  const staticFilterKeys = new Set(Object.keys(options?.staticFilters ?? {}))

  let route: ReturnType<typeof useRoute> | undefined
  let router: ReturnType<typeof useRouter> | undefined
  if (options?.syncUrl) {
    try {
      route = useRoute()
      router = useRouter()
    } catch {
      // Router not available (e.g. in tests)
    }
  }

  // Initialise dynamic filters — URL params override defaults for non-static keys
  const initialFilters: Record<string, unknown> = { ...options?.filters }
  if (route?.query) {
    for (const [key, val] of Object.entries(route.query)) {
      if (!RESERVED_URL_KEYS.has(key) && !staticFilterKeys.has(key) && val != null) {
        initialFilters[key] = String(val)
      }
    }
  }

  const data = ref<T[]>([]) as { value: T[] }
  const meta = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const initialLoading = ref(true)
  const search = ref(route?.query?.search ? String(route.query.search) : '')
  const sortBy = ref(
    route?.query?.sort_by ? String(route.query.sort_by) : (options?.defaultSort?.field ?? ''),
  )
  const sortDir = ref<'asc' | 'desc'>(
    (route?.query?.sort_dir === 'asc' || route?.query?.sort_dir === 'desc')
      ? route.query.sort_dir
      : (options?.defaultSort?.dir ?? 'asc'),
  )
  const filters = ref<Record<string, unknown>>(initialFilters)
  const currentPage = ref(parsePositiveInt(route?.query?.page, 1))
  const perPage = ref(parsePositiveInt(route?.query?.per_page, defaultPerPage))

  let abortController: AbortController | null = null

  function buildParams(): Record<string, string | number> {
    const params: Record<string, string | number> = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (search.value) {
      params.search = search.value
    }

    if (sortBy.value) {
      params.sort_by = sortBy.value
      params.sort_dir = sortDir.value
    }

    // Static filters — always sent, never in URL
    for (const [key, value] of Object.entries(options?.staticFilters ?? {})) {
      if (value !== null && value !== undefined && value !== '') {
        params[key] = value as string | number
      }
    }

    // Dynamic filters — user-settable, synced to URL
    for (const [key, value] of Object.entries(filters.value)) {
      if (value !== null && value !== undefined && value !== '') {
        params[key] = value as string | number
      }
    }

    return params
  }

  async function fetch(): Promise<void> {
    if (abortController) {
      abortController.abort()
    }

    abortController = new AbortController()
    loading.value = true

    try {
      const response = await api.get(endpoint, {
        params: buildParams(),
        signal: abortController.signal,
      })

      const body = response.data
      data.value = body.data
      meta.value = body.meta ?? null
    } catch (error: unknown) {
      if (error instanceof Error && error.name === 'CanceledError') {
        return
      }
      data.value = []
      meta.value = null
    } finally {
      loading.value = false
      initialLoading.value = false
    }
  }

  function syncUrlParams(): void {
    if (!router || !route?.query) return
    const query: Record<string, string> = {}

    // search
    if (search.value) query.search = search.value

    // sort — include whenever explicitly set
    if (sortBy.value) {
      query.sort_by = sortBy.value
      query.sort_dir = sortDir.value
    }

    // dynamic filters (exclude static filter keys)
    for (const [key, val] of Object.entries(filters.value)) {
      if (!staticFilterKeys.has(key) && val !== null && val !== undefined && val !== '') {
        query[key] = String(val)
      }
    }

    // page / per_page
    if (currentPage.value > 1) query.page = String(currentPage.value)
    if (perPage.value !== defaultPerPage) query.per_page = String(perPage.value)

    router.replace({ query })
  }

  function setSort(field: string): void {
    if (sortBy.value === field) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = field
      sortDir.value = 'asc'
    }
    currentPage.value = 1
    syncUrlParams()
    fetch()
  }

  function setPage(page: number): void {
    currentPage.value = page
    syncUrlParams()
    fetch()
  }

  function setPerPage(newPerPage: number): void {
    perPage.value = newPerPage
    currentPage.value = 1
    syncUrlParams()
    fetch()
  }

  function setFilter(key: string, value: unknown): void {
    filters.value = { ...filters.value, [key]: value }
    currentPage.value = 1
    syncUrlParams()
    fetch()
  }

  async function refresh(): Promise<void> {
    await fetch()
  }

  // Search watcher — AppSearchBar already debounces input, so fetch immediately.
  watch(search, () => {
    currentPage.value = 1
    syncUrlParams()
    fetch()
  })

  // Fetch immediately if requested (default: true)
  if (options?.immediate !== false) {
    fetch()
  }

  return {
    get data() { return data.value },
    get meta() { return meta.value },
    get loading() { return loading.value },
    get initialLoading() { return initialLoading.value },
    get search() { return search.value },
    set search(val: string) { search.value = val },
    get sortBy() { return sortBy.value },
    get sortDir() { return sortDir.value },
    get filters() { return filters.value },
    get currentPage() { return currentPage.value },
    get perPage() { return perPage.value },
    /** Pre-built props for AppPagination — use with v-bind. */
    get paginationProps() {
      return meta.value
        ? {
            currentPage: meta.value.current_page,
            lastPage: meta.value.last_page,
            total: meta.value.total,
            from: meta.value.from,
            to: meta.value.to,
            perPage: meta.value.per_page,
          }
        : undefined
    },
    /** Create a simple filter handler for a given key. */
    createFilterHandler(key: string) {
      return (value: string | number | null) => {
        setFilter(key, value || undefined)
      }
    },
    fetch,
    setSort,
    setPage,
    setPerPage,
    setFilter,
    refresh,
  }
}
