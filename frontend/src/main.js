// src/main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import App from './App.vue'
import { useAuthStore } from '@/stores/auth'
import router from './router'
import './assets/main.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(Toast, {
  position: 'top-right',
  timeout: 3500,
  closeOnClick: true,
  pauseOnHover: true,
  hideProgressBar: false,
  toastClassName: 'font-sans text-sm',
})

app.mount('#app')

// Revalidamos la sesión guardada contra el servidor, pero SIN bloquear el
// montaje: la navegación ya funciona con el token de localStorage, así que
// el usuario entra directo aunque abra una pestaña nueva o reinicie el
// navegador. Si el servidor responde 401, el store cierra la sesión solo.
useAuthStore().refreshUser()
