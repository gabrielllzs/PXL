import { createApp, defineAsyncComponent } from 'vue'
import './bootstrap'; // Ensure axios is loaded


const Canvas = defineAsyncComponent(() => import('./views/PixelMapView.vue'));
const Login = defineAsyncComponent(() => import('./components/auth/login.vue'));
const AdminPanel = defineAsyncComponent(() => import('./components/admin/AdminPanel.vue'));
const Support = defineAsyncComponent(() => import('./components/support/SupportForm.vue'));

const app = createApp({})

app.component('canvas-view', Canvas)
app.component('login-view', Login)
app.component('admin-panel', AdminPanel)
app.component('support-form', Support)

app.mount('#app')
