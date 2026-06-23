/**
 * Build a careers-portal URL that adapts to the current host.
 *
 * The base prefix is read once from the `<meta name="careers-base">` tag
 * rendered by the Blade layout. On the careers subdomain the prefix is
 * empty (the domain itself is the portal); elsewhere it's `/careers`.
 */

let cached: string | null = null

function readBase(): string {
  if (cached !== null) return cached
  const tag = document.querySelector('meta[name="careers-base"]')
  cached = tag?.getAttribute('content') ?? '/careers'
  return cached
}

export function careersUrl(suffix: string = ''): string {
  const base = readBase()
  const trimmed = suffix.replace(/^\/+/, '')

  if (trimmed === '') {
    return base === '' ? '/' : base
  }

  return `${base}/${trimmed}`
}
