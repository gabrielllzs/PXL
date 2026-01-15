import { createApp, defineAsyncComponent } from 'vue'
import './bootstrap'; // Ensure axios is loaded


const Canvas = defineAsyncComponent(() => import('./views/PixelMapView.vue'));
const AdminPanel = defineAsyncComponent(() => import('./components/admin/AdminPanel.vue'));
const Feedback = defineAsyncComponent(() => import('./components/Feedback/FeedbackView.vue'));

const app = createApp({})

app.component('canvas-view', Canvas)
app.component('admin-panel', AdminPanel)
app.component('feedback-view', Feedback)

app.mount('#app')
