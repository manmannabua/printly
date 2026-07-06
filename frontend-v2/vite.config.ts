import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  build: {
    sourcemap: false,
  },
  plugins: [
    vue(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'logo.png'],
      manifest: {
        name: 'Printly',
        short_name: 'Printly',
        description: 'Order prints, track pickup status, and chat with your print shop.',
        theme_color: '#2563eb',
        background_color: '#f8fafc',
        display: 'standalone',
        display_override: ['window-controls-overlay', 'standalone', 'browser'],
        orientation: 'portrait',
        scope: '/',
        start_url: '/',
        icons: [
          {
            src: '/pwa-192.png',
            sizes: '192x192',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/pwa-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any',
          },
          {
            src: '/maskable-icon-512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'maskable',
          },
        ],
      },
      workbox: {
        navigateFallback: '/index.html',
        cleanupOutdatedCaches: true,
        runtimeCaching: [
          {
            urlPattern: ({ request }) => request.destination === 'document',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'printly-pages',
              networkTimeoutSeconds: 3,
            },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/v1/s/') || url.pathname.startsWith('/api/v1/orders/'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'printly-public-api',
              networkTimeoutSeconds: 3,
              expiration: {
                maxEntries: 80,
                maxAgeSeconds: 60 * 60,
              },
              cacheableResponse: {
                statuses: [0, 200],
              },
            },
          },
          {
            urlPattern: ({ request }) => ['style', 'script', 'font', 'image'].includes(request.destination),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'printly-assets',
              expiration: {
                maxEntries: 120,
                maxAgeSeconds: 60 * 60 * 24 * 30,
              },
            },
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
    dedupe: ['vue', '@vue/runtime-core', '@vue/runtime-dom', '@vue/reactivity'],
  },
  server: {
    // 5173 matches the backend's SANCTUM_STATEFUL_DOMAINS / FRONTEND_URL, so
    // admin cookie auth works out of the box. strictPort: never silently drift
    // to 5175 (which isn't in the CORS/Sanctum allow-list → login just fails).
    port: 5173,
    strictPort: true,
    proxy: {
      '/api': {
        target: resolveApiTarget(),
        changeOrigin: true,
      },
      '/sanctum': {
        target: resolveApiTarget(),
        changeOrigin: true,
      },
    },
  },
})

/**
 * Resolve the backend API origin for the dev proxy.
 *
 * In dev with no env, default to http://localhost:8000 (the artisan serve
 * default) — this is intentional. On any non-dev script (e.g. a CI build
 * that accidentally runs `vite dev`), require VITE_API_BASE_URL to be set
 * so we never silently proxy to localhost.
 */
function resolveApiTarget(): string {
  const fromEnv = process.env.VITE_API_BASE_URL
  if (fromEnv && fromEnv.length > 0) {
    return fromEnv
  }
  if (process.env.CI) {
    throw new Error(
      'VITE_API_BASE_URL is required when running the Vite dev server in CI. ' +
      'Set it explicitly so the dev proxy does not fall back to http://localhost:8000.',
    )
  }
  return 'http://localhost:8000'
}
