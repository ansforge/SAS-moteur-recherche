import { createApp } from 'vue';
import { createPinia } from 'pinia';

import Vue3Sanitize from 'vue-3-sanitize';
import { sanitizeUrl } from '@braintree/sanitize-url';
import App from '@/modules/search-module/App.vue';

import sanitizeOptions from '@/const/sanitizeOptions';

import { clickOutside } from '@/directives';

const vueApp = createApp(App);
const pinia = createPinia();

vueApp.config.globalProperties.$sanitizeUrl = sanitizeUrl;
vueApp.directive('clickOutside', clickOutside);

vueApp
  .use(Vue3Sanitize, sanitizeOptions)
  .use(pinia)
  .mount('#sas-search-wrapper');
