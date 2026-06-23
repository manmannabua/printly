import './careers.css'
import { createApp } from 'vue'
import App from './App.vue'
import { useDarkMode } from './composables/useDarkMode'
import { bootCareersThemeFromDataset } from './composables/useCareersTheme'

const { init } = useDarkMode()
init()

// Apply tenant branding colors before mount so the first paint already
// reflects the saved colorTheme — otherwise the page flashes the default
// indigo gradient before the variables update.
bootCareersThemeFromDataset()

const el = document.getElementById('careers-app')
if (el) {
  createApp(App).mount(el)
}
