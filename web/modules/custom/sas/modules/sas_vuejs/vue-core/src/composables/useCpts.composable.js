import { computed } from 'vue';

import { SearchService } from '@/services';
import { useSearchData, useGeolocationData, useCpts } from '@/stores';

// eslint-disable-next-line import/prefer-default-export
export const useCptsDisplay = () => {
  const cptsStore = useCpts();
  const searchDataStore = useSearchData();

  const currentCptsCollection = computed(() => (searchDataStore.isFiltered
    ? cptsStore.cptsCollectionLvlOne
    : cptsStore.cptsCollectionLvlTwo));

  /**
   * @param {string} cptsFiness
   */
  function setCurrentCpts(cptsFiness) {
    currentCptsCollection.value.setCurrentDisplayedCpts(cptsFiness);
    cptsStore.currentSelectedCpts = currentCptsCollection.value.cptsCollection.find(
      (cpts) => cpts.ss_field_identifiant_finess === cptsFiness,
    );
  }

  async function initCPTSList() {
    const geolocationStore = useGeolocationData();

    if (geolocationStore.geolocation?.inseeCode) {
      const res = await SearchService.fetchCPTSListByInseeCode(geolocationStore.geolocation.inseeCode);
      const cptsToAdd = res.error_code ? [] : res.map((card) => ({ ...card, type: 'cpts' }));
      cptsStore.cptsCollectionLvlOne.setCptsCollection(cptsToAdd);
      cptsStore.cptsCollectionLvlTwo.setCptsCollection(cptsToAdd);
    }
  }

  /**
   * @param {import('@/types').ICard[]} list
   * @returns {import('@/types').ICard[]} The same list but with every cpts affiliated cards removed.
   * They get replaced by one copy of their CPTS card
   */
  function findCPTSCardToReplace(list) {
    let cardsToDisplay = [...list];

    for (let i = 0; ; i += 1) {
      if (i >= cardsToDisplay.length) break;

      const cptsFinessOfCurrentCard = cardsToDisplay[i].ss_sas_cpts_finess;

      if (cptsFinessOfCurrentCard in currentCptsCollection.value.cptsToFind) {
        cardsToDisplay = manageCPTSCardInsertion({
          cardList: cardsToDisplay,
          swapIndex: i,
          cptsFiness: cptsFinessOfCurrentCard,
        });

        currentCptsCollection.value.changeCptsToFind(cptsFinessOfCurrentCard, true);
      }
    }

    return cardsToDisplay;
  }

  /**
   * @param {object} _
   * @param {import('@/types').ICard[]} _.cardList
   * @param {number} _.swapIndex
   * @param {string} _.cptsFiness
   */
  function manageCPTSCardInsertion({ cardList, swapIndex, cptsFiness }) {
    const cptsCardToAdd = currentCptsCollection.value.cptsCollection.find((cpts) => cpts.ss_field_identifiant_finess === cptsFiness);

    // Swaps the current card with the cpts one
    cardList.splice(swapIndex, 1, cptsCardToAdd);

    return cardList.filter((card) => card.ss_sas_cpts_finess !== cptsFiness);
  }

  return {
    initCPTSList,
    findCPTSCardToReplace,
    setCurrentCpts,
    currentCptsCollection,
  };
};
