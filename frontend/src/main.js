import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import '@mdi/font/css/materialdesignicons.css'
import './styles/main.css'
import './styles/global.css'

const applyStoredPreferences = () => {
	const root = document.documentElement
	const raw = localStorage.getItem('ui_preferences')

	if (!raw) return

	try {
		const settings = JSON.parse(raw)
		root.classList.toggle('a11y-large-text', !!settings.largeText)
		root.classList.toggle('a11y-high-contrast', !!settings.highContrast)
		root.classList.toggle('a11y-reduced-motion', !!settings.reducedMotion)
		root.classList.toggle('ui-compact', !!settings.compactMode)
	} catch {
		// Ignore invalid persisted preferences.
	}
}

applyStoredPreferences()

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(vuetify)

app.mount('#app')
