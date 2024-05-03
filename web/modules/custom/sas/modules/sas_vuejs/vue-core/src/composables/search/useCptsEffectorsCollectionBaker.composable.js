import _isEmpty from 'lodash.isempty';
import {
  ref, watch,
} from 'vue';
import { useSearchApiCalls } from '@/composables/search/useSearchApiCalls.new.composable';
import { useICollectionBaker } from '@/composables/search/useICollectionBaker.composable';

import { BAKING_STATUS } from '@/const';

import { hashGenerator } from '@/helpers';

import { useGeolocationData, useCpts } from '@/stores';

/**
 * This composable is asked to aggregate a sanitized collections of cpts affiliated cards.
 *
 * It builds this collection from a multitude of networks calls.
 * First it calls a SolR endpoint to get a list of raw data of cards.
 * Using this uncomplete data, it calls the SAS API and the aggregator to bake a list of complete card following a list of special treatment.
 *
 * @param {object} _
 * @param {import('vue').Ref<Map<string, ICard>>} _.collection - The collection to populate
 * @param {import('vue').Ref<boolean>} _.shouldWeContinueBaking
 * @param {number} _.numberOfResultsPerSolrBatch
 * @param {number} _.numberOfApiLotPerSolrBatch
 * @param {import('vue').Ref<object>} _.filtersToMonitor
 * @param {boolean} _.withSlot
 */
// eslint-disable-next-line import/prefer-default-export
export function useCptsEffectorsCollectionBaker({
  collection,
  shouldWeContinueBaking,
  numberOfResultsPerSolrBatch = 100,
  numberOfApiLotPerSolrBatch = 4,
  filtersToMonitor,
}) {
  const self = {};

  const {
    setFilterFacetsToMonitor,
    splitSolrCardsToLots,
    numberOfCardsPerApiLot,
  } = useICollectionBaker({
    numberOfResultsPerSolrBatch,
    numberOfApiLotPerSolrBatch,
  });

  const geolocationStore = useGeolocationData();
  const cptsStore = useCpts();

  const { fetchCPTSResults, fetchApiResults } = useSearchApiCalls();

  /**
   * The random seed used by the backend to randomly sort the result
   */
  const searchSeed = ref(null);

  // buffer for SOLR results by packets of <numberOfResultsPerApiLot>
  /** @type {import('vue').Ref<import('@/types').SolrCard[][] | null>} */
  const solrSlicedRes = ref(null);

  /**
   * It tells us which solr page we want to fetch
   */
  const currentSolrPageNumber = ref(1);

  /**
   * @type {import('vue').Ref<string>}
   */
  self.bakingStatus = ref(BAKING_STATUS.NOT_OPEN_YET);

  const bakingSettings = ref({});

  /**
   * "Turns on the ovens".
   * The entrypoint that initializes the whole baking. It can be called only once
   * @param {object} _
   * @param {import('@/types').CPTSCard} _.cptsCard
   */
  self.init = async ({
    withSlot = true,
    isPrecise = false,
    cptsCard,
  }) => {
    if (self.bakingStatus.value !== BAKING_STATUS.NOT_OPEN_YET) return;

    cleanTheOvens();

    bakingSettings.value = {
      withSlot,
      isPrecise,
    };

    if (cptsCard) {
      collection.value.set(cptsCard.its_nid, cptsCard);
    }

    ringTheBell();
  };

  const stopWorking = watch(shouldWeContinueBaking, () => {
    if (self.bakingStatus.value === BAKING_STATUS.CLOSED) {
      stopWorking();
    }

    if (shouldWeContinueBaking.value) {
      ringTheBell();
    }
  });

  /**
   * Notifies the bakery that we would like a new batch
   */
  async function ringTheBell() {
    if (self.bakingStatus.value === BAKING_STATUS.PENDING && shouldWeContinueBaking.value) {
      doTheCooking({ ...bakingSettings.value });
    }
  }

  /**
   *
   * @param {object} _
   * @param {boolean} _.withSlot
   * @param {boolean} _.isPrecise
   */
  async function doTheCooking({ withSlot, isPrecise }) {
    if (!solrSlicedRes.value || solrSlicedRes.value.length === 0) {
      await fetchAndResolveSolrBatch({ withSlot, isPrecise });
    } else {
      await fetchAndResolveApisBatch({ withSlot });
    }
  }

  function cleanTheOvens() {
    currentSolrPageNumber.value = 1;
    searchSeed.value = hashGenerator();
    solrSlicedRes.value = null;
    // eslint-disable-next-line no-param-reassign
    filtersToMonitor.value = {};
    self.bakingStatus.value = BAKING_STATUS.PENDING;
    bakingSettings.value = {};
  }

  /**
   * fetch data from SOLR with given filters
   */
  async function fetchAndResolveSolrBatch({ withSlot, isPrecise }) {
    self.bakingStatus.value = BAKING_STATUS.FETCHING_SOLR;
    const solrRes = await fetchCPTSResults({
      finess: cptsStore.currentSelectedCpts?.ss_field_identifiant_finess,
      page: currentSolrPageNumber.value,
      searchSeed: isPrecise ? null : searchSeed.value,
      longitude: geolocationStore.geolocation?.longitude,
      latitude: geolocationStore.geolocation?.latitude,
      quantity: numberOfResultsPerSolrBatch,
      withSlot,
    });

    currentSolrPageNumber.value++;

    setFilterFacetsToMonitor({
      solrRes,
      currentFilterFacets: filtersToMonitor,
    });

    const solrCards = solrRes?.data;

    solrSlicedRes.value = splitSolrCardsToLots({
      solrCards,
      numberPerLot: numberOfCardsPerApiLot.value,
      expectedNumberOfLot: numberOfApiLotPerSolrBatch,
    });

    if (_isEmpty(solrSlicedRes.value)) {
      // This is the end of the cooking. We didn't fetch anything from the Solr call.
      self.bakingStatus.value = BAKING_STATUS.CLOSED;
      return;
    }

    self.bakingStatus.value = BAKING_STATUS.PENDING;
    ringTheBell();
  }

  /**
   * Launch sas api && agreg to get at least 5 cards
   */
  async function fetchAndResolveApisBatch({ withSlot }) {
    if (solrSlicedRes.value.length === 0) return;

    const currentSolrSlicedRes = solrSlicedRes.value.shift();

    self.bakingStatus.value = BAKING_STATUS.FETCHING_APIS;
    let results = await fetchApiResults({ solrArray: currentSolrSlicedRes, withSlot });

    if (withSlot) {
      results = results.filter((res) => (
        res.slotList
        && (
          res.slotList.today.length > 0
          || res.slotList.tomorrow.length > 0
          || res.slotList.afterTomorrow.length > 0
        )
      ));
    }

    results.forEach((res) => {
      collection.value.set(res.its_nid, res);
    });

    if (currentSolrSlicedRes.length < numberOfCardsPerApiLot.value) {
      // This is the end of the cooking. The slice isn't full. After this one, there will be no more items to fetch/compute.
      self.bakingStatus.value = BAKING_STATUS.CLOSED;
      return;
    }

    self.bakingStatus.value = BAKING_STATUS.PENDING;
    ringTheBell();
  }

  return self;
}
