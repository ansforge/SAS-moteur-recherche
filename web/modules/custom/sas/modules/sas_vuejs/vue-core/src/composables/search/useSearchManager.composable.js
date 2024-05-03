import {
  ref, computed, watch, onUnmounted,
} from 'vue';

import { useCollectionBaker as useGenericCollectionBaker } from '@/composables/search/useCollectionBaker.composable';
import { useDeckManager } from '@/composables/search/useDeckManager.composable';
import { usePagination } from '@/composables/usePagination.composable';

import { useCardCollection } from '@/stores';

import { BAKING_STATUS, SEARCH_STATUS } from '@/const';

/**
 * This composable is the backbone of the SAS search logic (frontend-wise).
 * It is tightly coupled with the card collection store.
 *
 * It juggles with a bunch of composable internally and exposes what's relevant for external components.
 * Here are the composables it works with:
 *
 * - *`useCollectionBaker`*: Solely responsible of fetching and aggregating the collection of cards. (*it can be specialized*)
 * - `useDeckManager`: Manage every cards and store their ids per every filters they have to facilitate retrieval later on.
 * - `usePagination`: Generic and agnostic composable to manage the current page number and its siblings.
 * @param {Object} _
 * @param {string} _.collectionId
 * @param {number} _.numberOfCardPerPage
 * @param {Function} _.useCollectionBaker - The composable function to be instantiated here responsible of building the collection of cards
 */
export function useSearchManager({
  collectionId,
  numberOfCardPerPage = 5,
  useCollectionBaker = useGenericCollectionBaker,
}) {
  const collectionStore = useCardCollection();

  /**
   * We put the shallowReactivity to false right now because we need to watch and react to the `slotData.orientation_count`
   */
  const collection = collectionStore.getCollection(collectionId, { shallowReactivity: false });

  const currentStatus = ref(SEARCH_STATUS.NOT_STARTED);

  const filtersToMonitor = ref({});

  /**
   * @type {import('vue').Ref<object>}
   */
  const currentAppliedFilters = ref({});

  const {
    currentDeck,
    filtersToDisplay,
  } = useDeckManager({ collection, currentAppliedFilters, filtersToMonitor });

  const numberOfLots = computed(() => Math.ceil(currentDeck.value.length / numberOfCardPerPage));

  const {
    currentLotNumber,
    actions: pageActions,
  } = usePagination({
    numberOfLots,
  });

  /**
   * @param {number} nid
   */
  function getPageWhereCardOfNidIs(nid) {
    const cardIndex = currentDeck.value.indexOf(nid);

    if (cardIndex === -1) return -1;

    return Math.ceil((cardIndex + 1) / numberOfCardPerPage);
  }

  // When applied filters change, we go back to the first page
  watch(currentAppliedFilters, () => {
    pageActions.goBackToFirstLot();
  });

  function computeCurrentDeckLot(lotIndex) {
    const startIndex = (lotIndex - 1) * numberOfCardPerPage;
    const endIndex = (lotIndex) * numberOfCardPerPage;

    return currentDeck.value.slice(startIndex, endIndex);
  }

  const currentDeckLot = computed(() => (computeCurrentDeckLot(currentLotNumber.value)));
  const currentDeckNextLot = computed(() => (computeCurrentDeckLot(currentLotNumber.value + 1)));

  /**
   * Retrieves the cards from the collection based on:
   *
   * - the current deck (list of ids)
   * - the current lot number
   * - the number of cards per page
   */
  const cardsToDisplay = computed(() => (currentDeckLot.value.map((id) => (collection.value.get(id)))));

  const shouldWeContinueBaking = computed(() => {
    if (currentStatus.value === SEARCH_STATUS.FINISHED) return false;

    const hasEnoughResult = currentDeckLot.value.length === numberOfCardPerPage
      && currentDeckNextLot.value.length === numberOfCardPerPage;

    if (hasEnoughResult) {
      currentStatus.value = SEARCH_STATUS.HAS_ENOUGH;
    }

    return !hasEnoughResult;
  });

  const {
    init: openTheBakery,
    bakingStatus,
  } = useCollectionBaker({
    collection,
    shouldWeContinueBaking,
    filtersToMonitor,

    numberOfResultsPerSolrBatch: 100,
    numberOfApiLotPerSolrBatch: 4,
  });

  watch(bakingStatus, () => {
    if (bakingStatus.value === BAKING_STATUS.CLOSED) {
      currentStatus.value = SEARCH_STATUS.FINISHED;
    } else {
      currentStatus.value = SEARCH_STATUS.LOADING;
    }
  });

  // MAP SECTION - to move outside of this composable
  const pinsToDisplay = computed(() => (currentDeck.value.map((id) => (collection.value.get(id)))));
  // *******

  onUnmounted(() => {
    collectionStore.deleteCollection(collectionId);
  });

  return {
    pageActions,
    currentLotNumber,
    numberOfLots,

    currentStatus,
    filtersToDisplay,
    cardsToDisplay,

    openTheBakery,

    currentAppliedFilters,

    getPageWhereCardOfNidIs,

    collectionId,

    // For the map pins
    pinsToDisplay,

    // for debug purposes
    currentDeck,
    currentDeckLot,
    currentDeckNextLot,
    collection,
    filtersToMonitor,
    bakingStatus,

    shouldWeContinueBaking,
  };
}
