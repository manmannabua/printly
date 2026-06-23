export interface PublicJobListing {
  id: string
  title: string
  slug: string
  location: string | null
  is_remote: boolean
  salary_range_min: string | null
  salary_range_max: string | null
  salary_currency: string | null
  show_salary: boolean
  published_at: string | null
  closes_at: string | null
  department: { id: string; name: string } | null
  employment_type: { id: string; name: string } | null
}

export interface PublicJobDetail extends PublicJobListing {
  description: string
  requirements: string
  benefits: string | null
  position: { id: string; title: string } | null
}

export interface ApplyJobData {
  id: string
  title: string
  slug: string
  location: string | null
  closes_at: string | null
  department: { name: string } | null
}

// CMS Types

export interface CmsPageData {
  title: string
  slug: string
  content: ContentBlock[]
  subtitle?: string | null
  hero_text_align?: 'center' | 'left' | 'right'
}

export type ContentBlockType =
  | 'rich_text'
  | 'hero'
  | 'values'
  | 'benefits'
  | 'testimonials'
  | 'cta'
  | 'image'
  | 'featured_jobs'

export interface ContentBlock {
  type: ContentBlockType
  data: Record<string, unknown>
}

export interface NavData {
  header: NavItem[]
  footer: NavItem[]
}

export interface NavItem {
  label: string
  url: string
  is_external: boolean
}

export interface SiteSettingsData {
  branding?: {
    logo_url?: string | null
    company_name?: string | null
    color_theme?: string | null
    logo_height?: string | null
    hero_title?: string | null
    hero_subtitle?: string | null
    hero_text_align?: 'center' | 'left' | 'right'
  }
  homepage?: {
    sections?: ContentBlock[]
  }
  contact?: {
    email?: string | null
    phone?: string | null
    address?: string | null
    map_embed_url?: string | null
    contact_form_recipient_email?: string | null
    contact_form_confirmation_message?: string | null
  }
}
