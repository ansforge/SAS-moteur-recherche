import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';

window.Drupal.behaviors.directorySnpCalendar = {
  attach(context) {
    // Find & filter placeholders in the context
    const placeholders = context.getElementsByClassName('directory-snp-calendar-placeholder');
    const filteredPlaceholders = Array.from(placeholders).filter((placeholder) => {
      const tabPanel = placeholder.closest('[role=tabpanel]') ?? placeholder.closest('#professional-health-content');
      if (!tabPanel) {
        // The app is not in a tab panel on etabs.
        return true;
      }

      // Check if this is the active/initial tab,
      // null : newly active tab panel
      // '0' : initial active tab pabel
      const tabIndex = tabPanel.getAttribute('tabindex');
      if (tabIndex !== null && tabIndex !== '0') {
        return false;
      }

      // Check if not already mounted
      if (placeholder.classList.contains('directory-snp-calendar-mounted')) {
        return false;
      }

      return true;
    });

    filteredPlaceholders.forEach((placeholder) => {
      placeholder.classList.add('directory-snp-calendar-mounted');
      const scheduleId = placeholder.getAttribute('data-schedule-id');

      const pinia = createPinia();

      // Tip: if schedule-id is null : DO NOT query the backend and display empty calendar
      createApp(App)
        .provide('scheduleId', scheduleId)
        .use(pinia)
        .mount(placeholder);
    });
  },
  // detach: function (context) {
  // @todo 1. implement detach on tabs
  // @todo 2. unmount the vue app on closed tabs if we want real optimisation
  // }
};
