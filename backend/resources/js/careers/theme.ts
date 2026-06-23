export const THEME_PRESETS = {
  indigo: {
    '--c-primary': '#4f46e5',
    '--c-primary-hover': '#4338ca',
    '--c-primary-light': '#eef2ff',
    '--c-primary-text': '#3730a3',
    '--c-gradient-from': '#4f46e5',
    '--c-gradient-via': '#7c3aed',
    '--c-gradient-to': '#6d28d9',
    '--c-accent-border': '#a5b4fc',
    '--c-spinner-track': '#c7d2fe',
  },
  blue: {
    '--c-primary': '#2563eb',
    '--c-primary-hover': '#1d4ed8',
    '--c-primary-light': '#eff6ff',
    '--c-primary-text': '#1e40af',
    '--c-gradient-from': '#2563eb',
    '--c-gradient-via': '#0ea5e9',
    '--c-gradient-to': '#0891b2',
    '--c-accent-border': '#93c5fd',
    '--c-spinner-track': '#bfdbfe',
  },
  emerald: {
    '--c-primary': '#059669',
    '--c-primary-hover': '#047857',
    '--c-primary-light': '#ecfdf5',
    '--c-primary-text': '#065f46',
    '--c-gradient-from': '#059669',
    '--c-gradient-via': '#0d9488',
    '--c-gradient-to': '#0d9488',
    '--c-accent-border': '#6ee7b7',
    '--c-spinner-track': '#a7f3d0',
  },
  rose: {
    '--c-primary': '#e11d48',
    '--c-primary-hover': '#be123c',
    '--c-primary-light': '#fff1f2',
    '--c-primary-text': '#9f1239',
    '--c-gradient-from': '#e11d48',
    '--c-gradient-via': '#db2777',
    '--c-gradient-to': '#db2777',
    '--c-accent-border': '#fda4af',
    '--c-spinner-track': '#fecdd3',
  },
  amber: {
    '--c-primary': '#d97706',
    '--c-primary-hover': '#b45309',
    '--c-primary-light': '#fffbeb',
    '--c-primary-text': '#92400e',
    '--c-gradient-from': '#d97706',
    '--c-gradient-via': '#ea580c',
    '--c-gradient-to': '#ea580c',
    '--c-accent-border': '#fcd34d',
    '--c-spinner-track': '#fde68a',
  },
  red: {
    '--c-primary': '#dc2626',
    '--c-primary-hover': '#b91c1c',
    '--c-primary-light': '#fef2f2',
    '--c-primary-text': '#991b1b',
    '--c-gradient-from': '#dc2626',
    '--c-gradient-via': '#b91c1c',
    '--c-gradient-to': '#991b1b',
    '--c-accent-border': '#fca5a5',
    '--c-spinner-track': '#fecaca',
  },
  slate: {
    '--c-primary': '#475569',
    '--c-primary-hover': '#334155',
    '--c-primary-light': '#f8fafc',
    '--c-primary-text': '#1e293b',
    '--c-gradient-from': '#475569',
    '--c-gradient-via': '#334155',
    '--c-gradient-to': '#1e293b',
    '--c-accent-border': '#94a3b8',
    '--c-spinner-track': '#cbd5e1',
  },
} as const

export type ThemeName = keyof typeof THEME_PRESETS

export const THEME_NAMES: ThemeName[] = ['indigo', 'blue', 'emerald', 'rose', 'red', 'amber', 'slate']

export const THEME_LABELS: Record<ThemeName, string> = {
  indigo: 'Indigo',
  blue: 'Blue',
  emerald: 'Emerald',
  rose: 'Rose',
  red: 'Red',
  amber: 'Amber',
  slate: 'Slate',
}

export const DEFAULT_THEME: ThemeName = 'indigo'

export function getThemeStyle(name?: string | null): Record<string, string> {
  const key = (name && name in THEME_PRESETS ? name : DEFAULT_THEME) as ThemeName
  return { ...THEME_PRESETS[key] }
}
