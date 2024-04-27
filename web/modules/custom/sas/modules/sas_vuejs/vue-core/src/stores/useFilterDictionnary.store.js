import { defineStore } from 'pinia';

import { ref } from 'vue';

/* eslint-disable import/prefer-default-export */
export const useFilterDictionnary = defineStore('useFilterDictionnary', () => {
  const filterTypeLabels = ref({});

  function setFilterTypeLabels(labelTypes) {
    filterTypeLabels.value = labelTypes;
  }

  return {
    filterTypeLabels,
    setFilterTypeLabels,
  };
});
