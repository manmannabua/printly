// Shared Printly types. Domain-specific types (stores, catalog, orders, etc.)
// are added per planning/01-data-model-and-architecture.md.

export type Upload = {
  path: string
  url: string
  name: string
  size: number
}

/** Embedded 3D / video tour providers (reused by AppMultiUpload / tour embed util). */
export type TourProvider = 'matterport' | 'youtube' | 'vimeo' | 'kuula'

/** High-level counts shown on the admin dashboard (skeleton). */
export interface DashboardCounts {
  users: number
  active_users: number
}
