import { defineStore } from 'pinia';

import { ref } from 'vue';

/* eslint-disable import/prefer-default-export */
export const useMarketPlaceEditorsList = defineStore('useMarketPlaceEditorsList', () => {
  const activeEditorsList = ref([]);

  function setActiveEditorsList(editors) {
    activeEditorsList.value = editors;
  }

  return {
    activeEditorsList,
    setActiveEditorsList,
  };
});
