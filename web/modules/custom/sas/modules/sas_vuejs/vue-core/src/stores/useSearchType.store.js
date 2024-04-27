import { defineStore } from 'pinia';
import { ref } from 'vue';

/* eslint-disable import/prefer-default-export */
export const useSearchType = defineStore('useSearchType', () => {
  const isSearchStructure = ref(false);

  function setIsSearchStructure(val) {
    isSearchStructure.value = val;
  }

  return {
    isSearchStructure,
    setIsSearchStructure,
  };
});
