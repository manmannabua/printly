import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  build: {
    sourcemap: false,
  },
  plugins: [
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
    dedupe: ['vue', '@vue/runtime-core', '@vue/runtime-dom', '@vue/reactivity'],
  },
  server: {
    // 5173 matches the backend's SANCTUM_STATEFUL_DOMAINS / FRONTEND_URL, so
    // admin cookie auth works out of the box.
    port: 5173,
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
