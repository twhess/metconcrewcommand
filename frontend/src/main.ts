import { createApp } from 'vue'
import { Quasar, Notify } from 'quasar'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/dist/quasar.css'

const app = createApp(App)
const pinia = createPinia()

app.use(Quasar, {
  plugins: {
    Notify
  },
  config: {
    notify: {}
  }
})

app.use(pinia)
app.use(router)

app.mount('#app')
