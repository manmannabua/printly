/**
 * Apply a tenant's chosen color theme to the careers SPA at boot.
 *
 * The careers stylesheet (careers.css) references CSS variables like
 * `--c-primary`, `--c-gradient-from`, etc., but historically nothing set
 * them — so saved branding colors had no runtime effect. This module owns
 * the theme palette and writes each variable onto :root the moment the app
 * mounts, before the first paint.
 */

interface ThemePalette {
  /** Brand primary — solid backgrounds, links, focus rings. */
  primary: string
  /** Hover/active state for primary surfaces. */
  hover: string
  /** Gradient endpoint A (top-left). */
  from: string
  /** Gradient midpoint (used by bg-c-gradient three-stop). */
  via: string
  /** Gradient endpoint B (bottom-right). */
  to: string
}

const THEMES: Record<string, ThemePalette> = {
  indigo: { primary: '#4f46e5', hover: '#4338ca', from: '#4f46e5', via: '#7c3aed', to: '#9333ea' },
  blue: { primary: '#2563eb', hover: '#1d4ed8', from: '#2563eb', via: '#0e7490', to: '#0891b2' },
  emerald: { primary: '#059669', hover: '#047857', from: '#059669', via: '#0c8b7e', to: '#0d9488' },
  rose: { primary: '#e11d48', hover: '#be123c', from: '#e11d48', via: '#d6206f', to: '#db2777' },
  red: { primary: '#dc2626', hover: '#b91c1c', from: '#dc2626', via: '#b81e23', to: '#991b1b' },
  amber: { primary: '#d97706', hover: '#b45309', from: '#d97706', via: '#e36106', to: '#ea580c' },
  slate: { primary: '#475569', hover: '#334155', from: '#475569', via: '#334155', to: '#1e293b' },
}

/**
 * Set every theme-driven CSS variable on :root. Derived shades
 * (`--c-primary-light`, `--c-accent-border`, `--c-spinner-track`) are
 * computed at runtime via `color-mix` so we don't have to hand-pick a
 * lighter hex per theme.
 */
export function applyCareersTheme(themeName?: string | null): void {
  const t = (themeName && THEMES[themeName]) || THEMES.indigo
  const root = document.documentElement.style

  root.setProperty('--c-primary', t.primary)
  root.setProperty('--c-primary-hover', t.hover)
  root.setProperty('--c-primary-light', `color-mix(in srgb, white 70%, ${t.primary})`)
  root.setProperty('--c-primary-text', '#ffffff')
  root.setProperty('--c-accent-border', `color-mix(in srgb, white 60%, ${t.primary})`)
  root.setProperty('--c-spinner-track', `color-mix(in srgb, white 85%, ${t.primary})`)
  root.setProperty('--c-gradient-from', t.from)
  root.setProperty('--c-gradient-via', t.via)
  root.setProperty('--c-gradient-to', t.to)
}

/** Read siteSettings off the careers-app data attribute and theme accordingly. */
export function bootCareersThemeFromDataset(): void {
  const el = document.getElementById('careers-app')
  if (!el?.dataset.siteSettings) {
    applyCareersTheme(null)
    return
  }
  try {
    const parsed = JSON.parse(el.dataset.siteSettings) as { branding?: { color_theme?: string } }
    applyCareersTheme(parsed.branding?.color_theme ?? null)
  } catch {
    applyCareersTheme(null)
  }
}
