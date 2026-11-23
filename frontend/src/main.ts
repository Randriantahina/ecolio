import './style.css'
import { createEcolioApp } from './app/create-app'

const { app } = createEcolioApp({ apiBaseUrl: import.meta.env.VITE_API_URL ?? '/api/v1' })
app.mount('#app')
