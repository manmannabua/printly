import type { TourProvider } from '@/types/printly'

/**
 * Turn a (user-supplied) 3D-tour URL into a safe, embeddable iframe `src`.
 *
 * Security: only https URLs whose host is on the provider allowlist are accepted,
 * and extracted ids are restricted to `[A-Za-z0-9_-]` so they can't smuggle extra
 * query/path segments into the rebuilt embed URL. Anything else returns `null`
 * (the caller should then hide the tour button).
 */

const ID = /^[A-Za-z0-9_-]+$/

function hostMatches(host: string, domain: string): boolean {
  return host === domain || host.endsWith('.' + domain)
}

function safeUrl(raw: string | null): URL | null {
  if (!raw) return null
  try {
    const u = new URL(raw)
    return u.protocol === 'https:' ? u : null
  } catch {
    return null
  }
}

function matterport(u: URL): string | null {
  if (!hostMatches(u.hostname, 'matterport.com')) return null
  const m = u.searchParams.get('m')
  if (!m || !ID.test(m)) return null
  return `https://my.matterport.com/show/?m=${m}`
}

function youtube(u: URL): string | null {
  let id: string | null = null
  if (hostMatches(u.hostname, 'youtu.be')) {
    id = u.pathname.slice(1)
  } else if (hostMatches(u.hostname, 'youtube.com')) {
    id = u.searchParams.get('v') ?? (u.pathname.startsWith('/embed/') ? u.pathname.slice('/embed/'.length) : null)
  }
  if (!id || !ID.test(id)) return null
  return `https://www.youtube.com/embed/${id}`
}

function vimeo(u: URL): string | null {
  if (!hostMatches(u.hostname, 'vimeo.com')) return null
  // Either vimeo.com/<id> or player.vimeo.com/video/<id>.
  const segments = u.pathname.split('/').filter(Boolean)
  const id = segments[segments.length - 1] ?? null
  if (!id || !/^[0-9]+$/.test(id)) return null
  return `https://player.vimeo.com/video/${id}`
}

function kuula(u: URL): string | null {
  if (!hostMatches(u.hostname, 'kuula.co')) return null
  // Kuula share/post URLs are themselves embeddable; keep the path + query as-is.
  return `https://kuula.co${u.pathname}${u.search}`
}

const BUILDERS: Record<TourProvider, (u: URL) => string | null> = {
  matterport,
  youtube,
  vimeo,
  kuula,
}

/**
 * Resolve an embeddable iframe src for the given tour URL + provider.
 * Returns `null` when the URL is missing, not https, off-allowlist, or malformed.
 */
export function tourEmbedSrc(
  tourUrl: string | null | undefined,
  provider: TourProvider | null | undefined,
): string | null {
  const u = safeUrl(tourUrl ?? null)
  if (!u) return null

  if (provider && BUILDERS[provider]) {
    return BUILDERS[provider](u)
  }

  // No provider hint â€” try each builder; the host allowlist still gates it.
  for (const build of Object.values(BUILDERS)) {
    const src = build(u)
    if (src) return src
  }
  return null
}
