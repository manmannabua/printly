<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import AppInput from '@/components/ui/AppInput.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppIcon from '@/components/common/AppIcon.vue'

const props = withDefaults(defineProps<{
  latitude?: number | null
  longitude?: number | null
  label?: string
  /** When set, draws a coverage circle (km) around the pin — used for agent service radius. */
  radiusKm?: number | null
  height?: string
  disabled?: boolean
  helpText?: string
}>(), {
  height: '320px',
})

const emit = defineEmits<{
  'update:latitude': [v: number | null]
  'update:longitude': [v: number | null]
}>()

// Metro Manila — sensible default centre for this marketplace.
const DEFAULT_CENTER: [number, number] = [14.5995, 120.9842]

const mapEl = ref<HTMLElement | null>(null)
let map: L.Map | null = null
let marker: L.Marker | null = null
let circle: L.Circle | null = null
// Guard so our own emits don't bounce back through the props watcher and fight the map.
let internalUpdate = false

const search = ref('')
const searching = ref(false)
const searchError = ref<string | null>(null)
const showManual = ref(false)

const pinIcon = L.divIcon({
  className: 'atlas-pin',
  html:
    '<svg width="30" height="38" viewBox="0 0 30 38" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M15 0C6.7 0 0 6.7 0 15c0 10.5 13.4 22 14 22.5a1.5 1.5 0 0 0 2 0C16.6 37 30 25.5 30 15 30 6.7 23.3 0 15 0Z" fill="#1c6b57"/>' +
    '<circle cx="15" cy="15" r="6" fill="#fafaf8"/></svg>',
  iconSize: [30, 38],
  iconAnchor: [15, 38],
})

function round(n: number): number {
  return Math.round(n * 1e6) / 1e6
}

function emitCoords(lat: number | null | undefined, lng: number | null | undefined): void {
  internalUpdate = true
  emit('update:latitude', lat == null ? null : round(lat))
  emit('update:longitude', lng == null ? null : round(lng))
  // Release the guard after the parent prop round-trip settles.
  setTimeout(() => { internalUpdate = false }, 0)
}

function drawCircle(): void {
  if (!map) return
  if (props.radiusKm && props.radiusKm > 0 && marker) {
    const center = marker.getLatLng()
    const radius = props.radiusKm * 1000
    if (!circle) {
      circle = L.circle(center, {
        radius,
        color: '#1c6b57',
        weight: 1,
        fillColor: '#1c6b57',
        fillOpacity: 0.08,
      }).addTo(map)
    } else {
      circle.setLatLng(center)
      circle.setRadius(radius)
    }
  } else if (circle) {
    circle.remove()
    circle = null
  }
}

function setMarker(lat: number, lng: number, recenter = true): void {
  if (!map) return
  if (!marker) {
    marker = L.marker([lat, lng], { draggable: !props.disabled, icon: pinIcon }).addTo(map)
    marker.on('dragend', () => {
      const p = marker!.getLatLng()
      emitCoords(p.lat, p.lng)
      drawCircle()
    })
  } else {
    marker.setLatLng([lat, lng])
  }
  drawCircle()
  if (recenter) map.setView([lat, lng], Math.max(map.getZoom() ?? 11, 14))
}

function removeMarker(): void {
  if (marker) { marker.remove(); marker = null }
  drawCircle()
}

function clearLocation(): void {
  removeMarker()
  emitCoords(null, null)
}

async function doSearch(): Promise<void> {
  const q = search.value.trim()
  if (!q) return
  searching.value = true
  searchError.value = null
  try {
    const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=' + encodeURIComponent(q)
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    if (!Array.isArray(data) || data.length === 0) {
      searchError.value = 'No match found. Try a more specific address.'
      return
    }
    const lat = parseFloat(data[0].lat)
    const lng = parseFloat(data[0].lon)
    setMarker(lat, lng, true)
    emitCoords(lat, lng)
  } catch {
    searchError.value = 'Search failed. Check your connection and try again.'
  } finally {
    searching.value = false
  }
}

function onManual(axis: 'lat' | 'lng', value: string | number): void {
  const n = value === '' || value == null ? null : Number(value)
  const lat = axis === 'lat' ? n : props.latitude
  const lng = axis === 'lng' ? n : props.longitude
  if (lat != null && lng != null) {
    setMarker(lat, lng, true)
    emitCoords(lat, lng)
  } else {
    emitCoords(lat, lng)
  }
}

onMounted(() => {
  if (!mapEl.value) return
  const hasCoords = props.latitude != null && props.longitude != null
  map = L.map(mapEl.value, { scrollWheelZoom: false, attributionControl: true })
  map.setView(hasCoords ? [props.latitude as number, props.longitude as number] : DEFAULT_CENTER, hasCoords ? 14 : 11)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  if (hasCoords) setMarker(props.latitude as number, props.longitude as number, true)

  if (!props.disabled) {
    map.on('click', (e: L.LeafletMouseEvent) => {
      setMarker(e.latlng.lat, e.latlng.lng, false)
      emitCoords(e.latlng.lat, e.latlng.lng)
    })
  }

  // The picker often mounts inside an animating modal; recompute size once visible.
  setTimeout(() => map?.invalidateSize(), 60)
  setTimeout(() => map?.invalidateSize(), 300)
})

onBeforeUnmount(() => {
  map?.remove()
  map = null
  marker = null
  circle = null
})

watch(() => [props.latitude, props.longitude], ([lat, lng]) => {
  if (internalUpdate) return
  if (lat != null && lng != null) setMarker(lat, lng, true)
  else removeMarker()
})

watch(() => props.radiusKm, () => drawCircle())
</script>

<template>
  <div>
    <label v-if="label" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
      {{ label }}
    </label>

    <!-- Address search -->
    <form class="mb-2 flex gap-2" @submit.prevent="doSearch">
      <AppInput
        v-model="search"
        icon="map-pin"
        placeholder="Search an address or place…"
        :disabled="disabled"
        class="flex-1"
      />
      <AppButton type="submit" variant="secondary" icon="search" :loading="searching" :disabled="disabled">
        Find
      </AppButton>
    </form>
    <p v-if="searchError" class="mb-2 text-sm text-danger-600 dark:text-danger-400">{{ searchError }}</p>

    <!-- Map -->
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
      <div ref="mapEl" :style="{ height }" class="w-full" role="application" aria-label="Location map" />
    </div>

    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm">
      <span class="text-gray-500 dark:text-gray-400">
        <template v-if="latitude != null && longitude != null">
          <AppIcon name="map-pin" :size="14" class="mr-1 inline text-estate-600" />
          {{ latitude.toFixed(5) }}, {{ longitude.toFixed(5) }}
        </template>
        <template v-else>Click the map or search an address to drop a pin.</template>
      </span>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="text-gray-500 underline-offset-2 hover:text-gray-700 hover:underline dark:text-gray-400"
          @click="showManual = !showManual"
        >
          {{ showManual ? 'Hide manual entry' : 'Enter coordinates' }}
        </button>
        <button
          v-if="latitude != null || longitude != null"
          type="button"
          class="text-danger-600 underline-offset-2 hover:underline dark:text-danger-400"
          :disabled="disabled"
          @click="clearLocation"
        >
          Clear
        </button>
      </div>
    </div>

    <!-- Manual coordinate fallback -->
    <div v-if="showManual" class="mt-2 grid grid-cols-2 gap-3">
      <AppInput
        :model-value="latitude ?? ''"
        type="number"
        label="Latitude"
        :disabled="disabled"
        step="any"
        @update:model-value="(v) => onManual('lat', v)"
      />
      <AppInput
        :model-value="longitude ?? ''"
        type="number"
        label="Longitude"
        :disabled="disabled"
        step="any"
        @update:model-value="(v) => onManual('lng', v)"
      />
    </div>

    <p v-if="helpText" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ helpText }}</p>
  </div>
</template>

<style scoped>
:deep(.leaflet-container) {
  font: inherit;
  background: #e7e1d5;
}
:deep(.atlas-pin) {
  background: transparent;
  border: none;
}
</style>
