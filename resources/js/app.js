import { createApp } from 'vue'
import Canvas from './views/PixelMapView.vue'
import Login from "./components/auth/login.vue";
import AdminPanel from "./components/admin/AdminPanel.vue";

const app = createApp({})
app.component('canvas-view', Canvas)
app.component('login-view', Login)
app.component('admin-panel', AdminPanel)
app.mount('#app')
