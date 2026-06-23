import { describe, it, expect } from 'vitest'
import { tourEmbedSrc } from './tourEmbed'

describe('tourEmbedSrc', () => {
  it('embeds Matterport show links', () => {
    expect(tourEmbedSrc('https://my.matterport.com/show/?m=abc123', 'matterport'))
      .toBe('https://my.matterport.com/show/?m=abc123')
  })

  it('embeds YouTube watch, short, and embed URLs', () => {
    expect(tourEmbedSrc('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube'))
      .toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
    expect(tourEmbedSrc('https://youtu.be/dQw4w9WgXcQ', 'youtube'))
      .toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
    expect(tourEmbedSrc('https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube'))
      .toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
  })

  it('embeds Vimeo links via the player host', () => {
    expect(tourEmbedSrc('https://vimeo.com/123456789', 'vimeo'))
      .toBe('https://player.vimeo.com/video/123456789')
  })

  it('keeps Kuula share URLs', () => {
    expect(tourEmbedSrc('https://kuula.co/share/abc?fs=1', 'kuula'))
      .toBe('https://kuula.co/share/abc?fs=1')
  })

  it('infers the provider from the host when none is given', () => {
    expect(tourEmbedSrc('https://youtu.be/dQw4w9WgXcQ', null))
      .toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
  })

  it('rejects non-https URLs', () => {
    expect(tourEmbedSrc('http://my.matterport.com/show/?m=abc', 'matterport')).toBeNull()
  })

  it('rejects hosts off the allowlist (incl. lookalike domains)', () => {
    expect(tourEmbedSrc('https://evil.com/show/?m=abc', 'matterport')).toBeNull()
    expect(tourEmbedSrc('https://youtube.com.evil.com/watch?v=x', 'youtube')).toBeNull()
    expect(tourEmbedSrc('https://notyoutube.com/watch?v=x', 'youtube')).toBeNull()
  })

  it('rejects malformed ids and missing params', () => {
    expect(tourEmbedSrc('https://my.matterport.com/show/', 'matterport')).toBeNull()
    expect(tourEmbedSrc('https://vimeo.com/not-a-number', 'vimeo')).toBeNull()
  })

  it('returns null for empty/garbage input', () => {
    expect(tourEmbedSrc(null, null)).toBeNull()
    expect(tourEmbedSrc('not a url', 'youtube')).toBeNull()
  })
})
