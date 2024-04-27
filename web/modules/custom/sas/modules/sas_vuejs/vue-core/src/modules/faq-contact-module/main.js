import { createApp } from 'vue';

import Vue3Sanitize from 'vue-3-sanitize';

import sanitizeOptions from '@/const/sanitizeOptions';
import App from './App.vue';

createApp(App)
  .use(Vue3Sanitize, sanitizeOptions)
  .mount('#sas-reorientation-wrapper');
