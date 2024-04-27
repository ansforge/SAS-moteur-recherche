import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { sanitizeUrl } from '@braintree/sanitize-url';
import App from './App.vue';

window.Drupal.behaviors.aggregPsCalendar = {
  attach(context) {
    // Find & filter placeholders in the context
    const placeholders = context.getElementsByClassName('aggregator-calendar-placeholder');
    const filteredPlaceholders = Array.from(placeholders).filter((placeholder) => {
      // The app is expected to be in a tab panel.
      const tabPanel = placeholder.closest('[role=tabpanel]') ?? placeholder.closest('#professional-health-content');
      if (!tabPanel) {
        console.error('Aggreg PS calendar Vue app should be in a tab panel.');
        return false;
      }

      // Check if this is the active/initial tab,
      // null : newly active tab panel
      // '0' : initial active tab pabel
      const tabIndex = tabPanel.getAttribute('tabindex');
      if (tabIndex !== null && tabIndex !== '0') {
        return false;
      }

      // Check if not already mounted
      if (tabPanel.classList.contains('aggreg-ps-calendar-mounted')) {
        return false;
      }

      return true;
    });

    filteredPlaceholders.forEach((placeholder) => {
      const tabPanel = placeholder.closest('[role=tabpanel]') ?? placeholder.closest('#professional-health-content');
      tabPanel.classList.add('aggreg-ps-calendar-mounted');
      const placeNid = placeholder.getAttribute('data-place-nid');

      // The data is to be found in window.drupalSettings['aggreg-ps-calendar'][placeNid]
      const vueApp = createApp(App);
      const pinia = createPinia();

      vueApp.config.globalProperties.$sanitizeUrl = sanitizeUrl;
      vueApp
        .provide('placeNid', placeNid)
        .use(pinia)
        .mount(placeholder);
    });
  },
  // detach: function (context) {
  // @todo 1. implement detach on tabs
  // @todo 2. unmount the vue app on closed tabs if we want real optimisation
  // }
};
