import { createApp } from 'vue';
import { createPinia } from 'pinia';

import Vue3Sanitize from 'vue-3-sanitize';
import { sanitizeUrl } from '@braintree/sanitize-url';
import sanitizeOptions from '@/const/sanitizeOptions';

import { clickOutside } from '@/directives';

import App from './App.vue';

const vueApp = createApp(App);
const pinia = createPinia();

vueApp.config.globalProperties.$sanitizeUrl = sanitizeUrl;
vueApp.directive('clickOutside', clickOutside);

vueApp
  .use(Vue3Sanitize, sanitizeOptions)
  .use(pinia)
  .mount('#sas-dashboard-wrapper');
