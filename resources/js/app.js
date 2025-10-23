import { createApp } from 'vue'
import ExampleComponent from './components/App.vue'

const app = createApp()
app.component('example-component', ExampleComponent)
app.mount('#app');
