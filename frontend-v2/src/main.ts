import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from '@/App.vue'
import router from '@/router'
import { canDirective } from '@/directives/can'
import { initDarkMode } from '@/composables/useDarkMode'
import '@fontsource-variable/bricolage-grotesque'
import '@fontsource-variable/hanken-grotesk'
import '@/assets/main.css'
import '@/pwa'

// Apply dark mode before app mounts to avoid flash
initDarkMode()

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.directive('can', canDirective)

app.mount('#app')
