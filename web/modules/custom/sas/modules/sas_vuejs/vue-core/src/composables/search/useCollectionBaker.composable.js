import _isEmpty from 'lodash.isempty';
import {
  ref, watch,
} from 'vue';
import { useSearchApiCalls } from '@/composables/search/useSearchApiCalls.new.composable';
import { useICollectionBaker } from '@/composables/search/useICollectionBaker.composable';

import { BAKING_STATUS } from '@/const';
import { hashGenerator } from '@/helpers';

/**
 * This composable is responsible of the creation, aggregation and management of a sanitized collections of cards.
 * It builds this collection from a multitude of networks calls.
 * First it calls a SolR endpoint to get a list of raw data of cards.
 * Using this uncomplete and messy data, it calls the SAS API and the aggregator to bake a list of complete card following a list of special treatment.
 *
 * @param {object} _
 * @param {import('vue').Ref<Map<string, ICard>>} _.collection - The collection to populate
 * @param {number} _.numberOfResultsPerSolrBatch
 * @param {number} _.numberOfApiLotPerSolrBatch
 * @param {import('vue').Ref<object>} _.filtersToMonitor
 * @param {boolean} _.withSlot
 */
// eslint-disable-next-line import/prefer-default-export
export function useCollectionBaker({
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

  const { fetchSolrResults, fetchApiResults } = useSearchApiCalls();

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
  */
  self.init = async ({ withSlot = true, isPrecise = false }) => {
    cleanTheOvens();
    bakingSettings.value = {
      withSlot,
      isPrecise,
    };
    ringTheBell();
  };

  const stopWorking = watch(shouldWeContinueBaking, () => {
    console.log(`[watch] shouldWeContinueBaking: ${shouldWeContinueBaking.value}`);

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
  function ringTheBell() {
    if (self.bakingStatus.value === BAKING_STATUS.PENDING && shouldWeContinueBaking.value) {
      doTheCooking({ ...bakingSettings.value });
    }
  }

  async function doTheCooking({ withSlot }) {
    if (!solrSlicedRes.value || solrSlicedRes.value.length === 0) {
      await fetchAndResolveSolrBatch({ withSlot });
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
  }

  /**
   * fetch data from SOLR with given filters
   */
  async function fetchAndResolveSolrBatch({ withSlot }) {
    self.bakingStatus.value = BAKING_STATUS.FETCHING_SOLR;

    const solrRes = await fetchSolrResults({
      withSlot,
      page: currentSolrPageNumber.value,
      searchSeed,
      quantity: numberOfResultsPerSolrBatch,
    });

    currentSolrPageNumber.value++;

    setFilterFacetsToMonitor({
      solrRes,
      currentFilterFacets: filtersToMonitor,
    });

    const solrCards = solrRes?.data?.grouped?.ss_field_custom_group?.groups?.map((solrCard) => solrCard?.doclist?.docs[0]) || [];

    solrSlicedRes.value = splitSolrCardsToLots({
      solrCards,
      numberPerLot: numberOfCardsPerApiLot.value,
      expectedNumberOfLot: numberOfApiLotPerSolrBatch,
    });

    if (_isEmpty(solrSlicedRes.value)) {
      // This is the end of the cooking. We didn't fetch anything from the Solr call.
      console.log('Closing the bakery: Empty Solr call');
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
    console.log(`${currentSolrSlicedRes.length} cards in current solr sliced res`);

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
      console.log('Closing the bakery: Uncomplete API call');
      self.bakingStatus.value = BAKING_STATUS.CLOSED;
      return;
    }

    self.bakingStatus.value = BAKING_STATUS.PENDING;
    ringTheBell();
  }

  return self;
}
