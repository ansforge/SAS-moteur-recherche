export default {
  getDrupalSetting(paramName) {
    return window.drupalSettings?.sas_vuejs?.parameters[paramName] ?? null;
  },
};
