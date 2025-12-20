import { createApp } from 'vue'
import Canvas from './views/PixelMapView.vue'
import Login from "./components/auth/login.vue";

const app = createApp({})
app.component('Canvas-View', Canvas)
app.component('Login-View', Login)
app.mount('#app')
