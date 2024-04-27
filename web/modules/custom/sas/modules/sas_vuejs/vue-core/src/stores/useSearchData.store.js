import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import _isEmpty from 'lodash.isempty';
import SolrPageDataModel from '@/models/search/SolrPayloadPageData.model';
import {
  SAS_SEARCH_FOR_AVAILABLE_SLOTS_FILTER_LABEL,
  SAS_SEARCH_OVERBOOKING_FILTER_LABEL,
  SAS_SEARCH_FOR_EMERGENCY_REDIRECTION_FILTER_LABEL,
} from '@/const';

/* eslint-disable import/prefer-default-export */
export const useSearchData = defineStore('searchData', () => {
  const isLoading = ref(true);
  const isFirstLoad = ref(true);

  // filtered solr pagination data
  const paginationData = ref(new SolrPageDataModel());

  // unfiltered solr pagination data
  const paginationDataUnfiltered = ref(new SolrPageDataModel());
  paginationDataUnfiltered.value.setPaginationQty(25);

  // all results
  const allResultsWithSlots = ref([]);
  const allResultsWithoutSlots = ref([]);
  const listNidWithSlots = ref(new Set());
  const listNidWithoutSlots = ref(new Set());

  /**
   * removes duplicated cards in the given list
   * @param {Array} listToCheck
   * @param {Boolean} filtered
   * @returns {Array}
   */
  function removeDuplicationsFromResults(listToCheck, filtered = true) {
    const currentSet = filtered ? listNidWithSlots.value : listNidWithoutSlots.value;
    const filteredList = listToCheck.filter((card) => !currentSet.has(card.its_nid));

    filteredList.forEach((card) => currentSet.add(card.its_nid));
    return filteredList;
  }

  /**
   * set results in default lists
   * @param {Array} list
   * @param {Boolean} filtered
   */
  function setAllResults(list, filtered = true) {
    if (filtered) {
      allResultsWithSlots.value = allResultsWithSlots.value.concat(removeDuplicationsFromResults(list));
    } else {
      allResultsWithoutSlots.value = allResultsWithoutSlots.value.concat(removeDuplicationsFromResults(list, false));
    }

    sortElementsIntoFilters(list, filtered);
    updateFilters();
    updateCurrentList();
  }

  // orientation update all results
  function setNewAllResultsWithSlots(newSlotDataFromApiRes, isFilteredSearch) {
    const allResult = isFilteredSearch ? allResultsWithSlots.value : allResultsWithoutSlots.value;
    const currentCardIndex = allResult.findIndex((res) => newSlotDataFromApiRes.doctorId === res.its_nid);
    const currentCard = currentCardIndex > -1 ? allResult[currentCardIndex] : {};
    const isError = !newSlotDataFromApiRes.data.slot || newSlotDataFromApiRes.data?.error || newSlotDataFromApiRes.status === 'error';
    if (!_isEmpty(currentCard) && currentCard?.slotList) {
      for (const property in currentCard.slotList) {
        if (currentCard.slotList[property].length) {
          const targetSlotIndex = currentCard.slotList[property].findIndex((element) => element.id === newSlotDataFromApiRes?.data?.slot?.id);
          if (targetSlotIndex !== -1) {
            const selectedSlot = currentCard.slotList[property][targetSlotIndex];
            const isSlot = selectedSlot.max_patients === -1;
            const fullTimeSlot = selectedSlot.max_patients !== -1 && selectedSlot.max_patients <= newSlotDataFromApiRes.data.slot.orientation_count;
            if (
              isError
              || isSlot
              || fullTimeSlot
              ) {
              if (isFilteredSearch) {
                // "Afficher les PS disposant de créneaux disponibles" radio selected
                allResultsWithSlots.value[currentCardIndex].slotList[property].splice(targetSlotIndex, 1);
              } else {
                // "Créneaux en sus des disponibilité" radio selected
                allResultsWithoutSlots.value[currentCardIndex].slotList[property].splice(targetSlotIndex, 1);
              }
            } else if (isFilteredSearch) {
              // not full time-slot case & "Afficher les PS disposant de créneaux disponibles" radio selected"
              allResultsWithSlots.value[currentCardIndex].slotList[property][targetSlotIndex].orientation_count = newSlotDataFromApiRes.data.slot.orientation_count;
            } else {
              // not full time-slot case & "Créneaux en sus des disponibilité" radio selected
              allResultsWithoutSlots.value[currentCardIndex].slotList[property][targetSlotIndex].orientation_count = newSlotDataFromApiRes.data.slot.orientation_count;
            }
            return;
          }
        }
      }
    }
  }

  // LRM
  function setPrefDoctorToResults(prefDoctorData) {
    if (prefDoctorData.length) {
      allResultsWithSlots.value = allResultsWithSlots.value.concat(prefDoctorData);
      updateCurrentList();
    }
  }

  // sorted results
  const resultsWithSlotsPerFilter = ref({});
  const resultsWithoutSlotsPerFilter = ref({});

  // all filters
  const filtersWithSlot = ref([]);

  /**
   * add new filters to filters list (with slots)
   * @param {Array} filters
   */
  function setFiltersWithSlot(filters) {
    const filtersKey = filtersWithSlot.value.map((filter) => filter.key);
    const filtersToAdd = filters.filter((filter) => !filtersKey.includes(filter.key));

    if (filtersToAdd.length) {
      filtersWithSlot.value = filtersWithSlot.value.concat(filtersToAdd);

      filtersToAdd.forEach((filtCat) => {
        if (
          filtCat.key
          && !resultsWithSlotsPerFilter.value[filtCat.key]
        ) {
          resultsWithSlotsPerFilter.value[filtCat.key] = {};
        }

        if (filtCat.items?.length) {
          filtCat.items.forEach((item) => {
            if (
              item.idItems
              && resultsWithSlotsPerFilter.value[filtCat.key]
              && !resultsWithSlotsPerFilter.value[filtCat.key][item.idItems]
            ) {
              resultsWithSlotsPerFilter.value[filtCat.key][item.idItems] = [];
            }
          });
        }
      });
    }
  }

  const filtersWithoutSlot = ref([]);

  /**
   * add new filters to filters list (without slots)
   * @param {Array} filters
   */
  function setFiltersWithoutSlot(filters) {
    const filtersKey = filtersWithoutSlot.value.map((filter) => filter.key);
    const filtersToAdd = filters.filter((filter) => !filtersKey.includes(filter.key));

    if (filtersToAdd.length) {
      filtersWithoutSlot.value = filtersWithoutSlot.value.concat(filtersToAdd);

      filtersToAdd.forEach((filtCat) => {
        if (
          filtCat.key
          && !resultsWithoutSlotsPerFilter.value[filtCat.key]
        ) {
          resultsWithoutSlotsPerFilter.value[filtCat.key] = {};
        }

        if (filtCat.items?.length) {
          filtCat.items.forEach((item) => {
            if (
              item.idItems
              && resultsWithoutSlotsPerFilter.value[filtCat.key]
              && !resultsWithoutSlotsPerFilter.value[filtCat.key][item.idItems]
            ) {
              resultsWithoutSlotsPerFilter.value[filtCat.key][item.idItems] = [];
            }
          });
        }
      });
    }
  }

  // displayed results
  const currentDisplayedList = ref('filtered');
  const isFiltered = computed(() => (currentDisplayedList.value === 'filtered'));

  /**
   * set current display to filtered / unfiltered
   * @param {String} current
   */
  function setCurrentDisplayedList(current) {
    currentDisplayedList.value = current;
    currentSelectedFilters.value = {};
    updateCurrentList();
  }
  const currentList = ref([]);

  function fuseResults(filters, resultsToFilter = {}) {
    let fusedList = [];

    filters.forEach((filter) => {
      const filterId = filter.includes('next') ? filter : parseInt(filter, 10);
      if (resultsToFilter[filterId]) {
        fusedList = [...fusedList, ...resultsToFilter[filterId]];
      }
    });

    // remove duplicates
    return Array.from(new Set(fusedList));
  }

  /**
   * Refresh the current list of items to display based on:
   *  - The state of the cluster
   *  - The applied filters
   */
  function updateCurrentList() {
    const nbrElements = 5;
    let displayedList = [];

    const hasCustomFilters = Object.values(customFilters.value).length > 0;
    const hasOtherFilters = Object.values(currentSelectedFilters.value).length > 0;
    const resultsToFilter = isFiltered.value
      ? resultsWithSlotsPerFilter.value
      : resultsWithoutSlotsPerFilter.value;

    if (hasOtherFilters) {
      displayedList = [];
      const listsPerFilterCat = {};

      // apply filters
      Object.keys(currentSelectedFilters.value).forEach((filterCat) => {
        listsPerFilterCat[filterCat] = fuseResults(
          currentSelectedFilters.value[filterCat],
          resultsToFilter[filterCat],
        );
      });

      // apply intersection of all categories
      Object.keys(listsPerFilterCat).forEach((filterCat) => {
        const listToIntersect = listsPerFilterCat[filterCat];

        if (!displayedList.length) {
          displayedList = listToIntersect;
        } else {
          displayedList = displayedList.filter((card) => listToIntersect.map((item) => item.its_nid).includes(card?.its_nid));
        }
      });

      // remove duplicates
      displayedList = Array.from(new Set(displayedList));
    } else {
      displayedList = (!hasCustomFilters && isFiltered.value) ? allResultsWithSlots.value : allResultsWithoutSlots.value;
    }

    const allRemainingCards = currentClusterItems.value.length > 0
      ? displayedList.filter((item) => currentClusterItems.value.includes(item.its_nid))
      : [...displayedList];

    // make pages with 5 cards each from the current list
    currentList.value = allRemainingCards.reduce((resultArray, item, index) => {
      const chunkIndex = Math.floor(index / nbrElements);

      if (!resultArray[chunkIndex]) {
        // eslint-disable-next-line no-param-reassign
        resultArray[chunkIndex] = [];
      }

      resultArray[chunkIndex].push(item);

      return resultArray;
    }, []);
  }

  const currentClusterItems = ref([]);

  /**
   * If the array passed in parameter is equal to the one currently displayed, we clear it
   * @param {number[]} nidList - The list of nid to set inside the cluster
   */
  function setCurrentClusterItems(nidList) {
    const shouldRemoveClusters = (
      nidList.length === currentClusterItems.value.length
      && nidList.every((value, index) => value === currentClusterItems.value[index])
    );

    currentClusterItems.value = shouldRemoveClusters
      ? []
      : [...nidList];

    updateCurrentList();
  }

  function getCurrentLot(id) {
    return currentList.value[id] || [];
  }

  // current filters
  const currentSelectedFilters = ref({});
  const customFilters = ref({});

  function setCurrentSelectedFilters(filters) {
    currentSelectedFilters.value = { ...filters };
  }

  function setCustomFilters(filter) {
    customFilters.value = { ...filter };
    currentSelectedFilters.value = {};

    Object.keys(resultsWithoutSlotsPerFilter.value).forEach((catKey) => {
      Object.keys(resultsWithoutSlotsPerFilter.value[catKey]).forEach((item) => {
        resultsWithoutSlotsPerFilter.value[catKey][item] = [];
      });
    });
  }

  const getCurrentFilters = computed(() => (isFiltered.value ? filtersWithSlot.value : filtersWithoutSlot.value));

  /**
   * sort cards into filters
   * @param {Array} list
   * @param {Boolean} filtered
   */
  function sortElementsIntoFilters(list, filtered = true) {
    const currentFilterlist = filtered ? resultsWithSlotsPerFilter.value : resultsWithoutSlotsPerFilter.value;

    /* eslint-disable guard-for-in */
    for (const catKey in currentFilterlist) {
      for (const filter in currentFilterlist[catKey]) {
        const cardsToAdd = (catKey === 'available_hours')
        ? list.filter((card) => card.slotTable[filter].length)
        : list.filter((card) => card[catKey] && card[catKey].includes(parseInt(filter, 10)));

        const cardsInFilter = new Set(currentFilterlist[catKey][filter].map((card) => card.its_nid));
        const cardsNotInFilterList = cardsToAdd.filter((card) => !cardsInFilter.has(card.its_nid));

        if (cardsNotInFilterList.length) {
          currentFilterlist[catKey][filter].push(...cardsNotInFilterList);
        }
      }
    }
    /* eslint-enable guard-for-in */
  }

  function resetFilters() {
    currentSelectedFilters.value = {};
  }

  function updateFilterVisibility(filters, resultsPerFilter) {
    filters.forEach((filterCat) => {
      if (resultsPerFilter[filterCat.key]) {
        filterCat.items.forEach((item) => {
          const currentIdItem = (filterCat.key !== 'available_hours')
          ? parseInt(item.idItems, 10)
          : item.idItems;

          // eslint-disable-next-line no-param-reassign
          item.isVisible = (
            resultsPerFilter[filterCat.key][currentIdItem]
            && resultsPerFilter[filterCat.key][currentIdItem].length > 0
          );
        });

        const nbrItemsDisabled = filterCat.items.filter((item) => (!item.isVisible));
        // eslint-disable-next-line no-param-reassign
        filterCat.isVisible = nbrItemsDisabled.length !== filterCat.items.length;
      }
    });
  }

  function updateFilters() {
    updateFilterVisibility(filtersWithSlot.value, resultsWithSlotsPerFilter.value);
    updateFilterVisibility(filtersWithoutSlot.value, resultsWithoutSlotsPerFilter.value);
  }

  // custom filters (versus filters from solR result)
  const customFiltersToDisplay = ref(
    {
      has_slot: {
        id: 'has_slot',
        label: SAS_SEARCH_FOR_AVAILABLE_SLOTS_FILTER_LABEL,
        type: 'radio',
        name: 'radio-filter',
        isSelected: false,
        isVisible: false,
      },
      bs_sas_overbooking: {
        id: 'bs_sas_overbooking',
        label: SAS_SEARCH_OVERBOOKING_FILTER_LABEL,
        type: 'radio',
        name: 'radio-filter',
        isSelected: false,
        isVisible: false,
      },
      bs_sas_forfait_reo: {
        id: 'bs_sas_forfait_reo',
        label: SAS_SEARCH_FOR_EMERGENCY_REDIRECTION_FILTER_LABEL,
        type: 'radio',
        name: 'radio-filter',
        isSelected: false,
        isVisible: false,
      },
    },
  );
  const defaultCustomFilter = 'has_slot';
  function updateCustomFilterValue(filterId, filterVal = {}) {
    if (customFiltersToDisplay.value[filterId]) {
      customFiltersToDisplay.value[filterId] = { ...customFiltersToDisplay.value[filterId], ...filterVal };
    }
  }

  return {
    isLoading,
    isFirstLoad,

    allResultsWithSlots,
    allResultsWithoutSlots,
    setAllResults,

    resultsWithSlotsPerFilter,
    resultsWithoutSlotsPerFilter,

    filtersWithSlot,
    setFiltersWithSlot,

    filtersWithoutSlot,
    setFiltersWithoutSlot,

    currentDisplayedList,
    setCurrentDisplayedList,

    currentList,
    updateCurrentList,

    currentClusterItems,
    setCurrentClusterItems,

    currentSelectedFilters,
    setCurrentSelectedFilters,

    getCurrentFilters,

    paginationData,
    paginationDataUnfiltered,

    isFiltered,

    customFilters,
    setCustomFilters,

    // Actions
    resetFilters,
    updateFilters,
    setPrefDoctorToResults,
    sortElementsIntoFilters,
    setNewAllResultsWithSlots,
    customFiltersToDisplay,
    updateCustomFilterValue,
    defaultCustomFilter,
    listNidWithSlots,
    listNidWithoutSlots,

    // Getters
    getCurrentLot,
  };
});
