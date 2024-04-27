import { createApp } from 'vue';

import Vue3Sanitize from 'vue-3-sanitize';
import { sanitizeUrl } from '@braintree/sanitize-url';
import sanitizeOptions from '@/const/sanitizeOptions';
import App from './App.vue';

const vueApp = createApp(App);
vueApp.config.globalProperties.$sanitizeUrl = sanitizeUrl;
vueApp
  .use(Vue3Sanitize, sanitizeOptions)
  .mount('#sas-formation-wrapper');
