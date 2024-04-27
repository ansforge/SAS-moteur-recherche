import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { sanitizeUrl } from '@braintree/sanitize-url';
import App from './App.vue';

const vueApp = createApp(App);
const pinia = createPinia();

vueApp.config.globalProperties.$sanitizeUrl = sanitizeUrl;
vueApp.use(pinia).mount('#time_slot_schedule');
