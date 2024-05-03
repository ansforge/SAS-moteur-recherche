import { defineStore } from 'pinia';

import { ref, watch } from 'vue';
import CptsCollection from '../models/search/CptsCollection.model';

/* eslint-disable import/prefer-default-export */
export const useCpts = defineStore('useCpts', () => {
  const cptsCollectionLvlOne = ref(new CptsCollection());
  const cptsCollectionLvlTwo = ref(new CptsCollection());

  /** @type {import('vue').Ref<import('@/types').CPTSCard> | null} */
  const currentSelectedCpts = ref(null);
  const showCptsPage = ref(false);

  watch(currentSelectedCpts, (newCpts) => {
    showCptsPage.value = !!newCpts;
  });

  return {
    cptsCollectionLvlOne,
    cptsCollectionLvlTwo,
    currentSelectedCpts,
    showCptsPage,
  };
});
