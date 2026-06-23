/**
 * Centralized date formatting utilities.
 * All user-facing dates should use these formatters for consistency.
 */

const dateShortFormatter = new Intl.DateTimeFormat('en-US', {
  month: 'short',
  day: 'numeric',
  year: 'numeric',
})

const dateTimeMediumFormatter = new Intl.DateTimeFormat('en-US', {
  month: 'short',
  day: 'numeric',
  year: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
  hour12: true,
})

const dateLongFormatter = new Intl.DateTimeFormat('en-US', {
  month: 'long',
  day: 'numeric',
  year: 'numeric',
})

const dateTimeLongFormatter = new Intl.DateTimeFormat('en-US', {
  month: 'long',
  day: 'numeric',
  year: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
  hour12: true,
})

/**
 * Format a date as "Feb 1, 2026"
 */
export function formatDateShort(date: string | Date | null | undefined): string {
  if (!date) return '—'
  const d = typeof date === 'string'
    ? new Date(date.length === 10 ? `${date}T00:00:00` : date)
    : date
  if (isNaN(d.getTime())) return '—'
  return dateShortFormatter.format(d)
}

/**
 * Format a date+time as "Feb 1, 2026, 10:30 AM"
 */
export function formatDateTimeMedium(date: string | Date | null | undefined): string {
  if (!date) return '—'
  const d = typeof date === 'string'
    ? new Date(date.length === 10 ? `${date}T00:00:00` : date)
    : date
  if (isNaN(d.getTime())) return '—'
  return dateTimeMediumFormatter.format(d)
}

/**
 * Format a date as "February 1, 2026"
 */
export function formatDateLong(date: string | Date | null | undefined): string {
  if (!date) return '—'
  const d = typeof date === 'string'
    ? new Date(date.length === 10 ? `${date}T00:00:00` : date)
    : date
  if (isNaN(d.getTime())) return '—'
  return dateLongFormatter.format(d)
}

/**
 * Format a date+time as "February 1, 2026 at 10:30 AM"
 */
export function formatDateTimeLong(date: string | Date | null | undefined): string {
  if (!date) return '—'
  const d = typeof date === 'string'
    ? new Date(date.length === 10 ? `${date}T00:00:00` : date)
    : date
  if (isNaN(d.getTime())) return '—'
  return dateTimeLongFormatter.format(d)
}
