(function (drupalSettings) {
  window.API = window.API || {}
  window.API = drupalSettings.sas_vuejs ? {...window.API, ...drupalSettings.sas_vuejs.parameters} : window.API;
} (drupalSettings));
