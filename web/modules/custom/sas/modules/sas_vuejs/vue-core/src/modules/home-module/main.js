import { createApp } from 'vue';
import { createPinia } from 'pinia';

import Vue3Sanitize from 'vue-3-sanitize';
import sanitizeOptions from '@/const/sanitizeOptions';

import App from './App.vue';

const pinia = createPinia();

createApp(App)
  .use(Vue3Sanitize, sanitizeOptions)
  .use(pinia)
  .mount('#sas-home-wrapper');
