/* eslint-disable camelcase, no-param-reassign */
import _isEqual from 'lodash.isequal';
import _isEmpty from 'lodash.isempty';

import { computed } from 'vue';

/**
 * This composable isn't supposed to be used as is inside components.
 * It exposes methods to be used by other CollectionBaker.
 * It also does shared internal logic
 *
 * @param {object} _
 * @param {number} _.numberOfResultsPerSolrBatch
 * @param {number} _.numberOfApiLotPerSolrBatch

 */
// eslint-disable-next-line import/prefer-default-export
export function useICollectionBaker({
  numberOfResultsPerSolrBatch,
  numberOfApiLotPerSolrBatch,
}) {
  const self = {};

  self.numberOfCardsPerApiLot = computed(() => (numberOfResultsPerSolrBatch / numberOfApiLotPerSolrBatch));

  if (!Number.isInteger(self.numberOfCardsPerApiLot.value)) {
    throw new Error('Invalid input: numberOfResultsPerSolrBatch divided by numberOfApiLotPerSolrBatch must returns an integer. Please adjust the values accordingly.');
  }

  if (self.numberOfCardsPerApiLot.value === 1) {
    throw new Error('Invalid input: numberOfApiLotPerSolrBatch can\'t be equal to numberOfResultsPerSolrBatch. Please adjust the value accordingly.');
  }

  /**
   * set filter facets for a given search
   * @param {object} _
   * @param {import('@/types/api/Solr').SolrSearchGeolocation} _.solrRes
   * @param {import('vue').Ref<Object>} _.currentFilterFacets
   */
  self.setFilterFacetsToMonitor = ({ solrRes, currentFilterFacets }) => {
    const { facet_fields } = solrRes.data?.facet_counts || {};

    // It means that the facets that we already monitor are exactly the same as the one given by the solr batch
    if (_isEqual(currentFilterFacets.value, facet_fields)) return;

    currentFilterFacets.value = facet_fields || {};

    // add sas custom freshFilters
    if (!currentFilterFacets.value.itm_establishment_types) {
      currentFilterFacets.value.itm_establishment_types = {};
    }

    // Adds the CPTS filter no matter what
    currentFilterFacets.value.itm_establishment_types['222605'] = 1;
  };

  /**
   * Splits solr cards in small batches
   * @param {object} _
   * @param {import('@/types').SolrCardData[]} _.solrCards
   * @param {number} _.numberPerLot
   * @param {number} _.expectedNumberOfLot - Used as a means of optimization.
   * For example, as a base let's say we are fetching 100 cards per solr call and want to dispatch them in lot of 25 cards.
   * After some calls, the solr call returns to us 25, 50 or 75 cards. Without this variable (which let us know we wanted to build 4 lots),
   * we couldn't assume that we arrived at the end of the solr list.
   * @returns {import('@/types').SolrCard[][]} returns a 2D array of SolrCard or an empty array
   */
  self.splitSolrCardsToLots = ({ solrCards, numberPerLot, expectedNumberOfLot = 0 }) => {
    if (!solrCards || _isEmpty(solrCards)) return [];

    const solrSlicedRes = [];

    const nbLots = Math.floor(solrCards.length / numberPerLot) + (solrCards.length % numberPerLot > 0 ? 1 : 0);
    for (let i = 0; i < nbLots; i++) {
      const sliceStart = i * numberPerLot;
      const sliceEnd = (i + 1) * numberPerLot;
      const sliceLimit = Math.min(sliceEnd, solrCards.length);
      const currentResults = solrCards.slice(sliceStart, sliceLimit);
      solrSlicedRes[i] = currentResults;
    }

    if (expectedNumberOfLot && solrSlicedRes.length < expectedNumberOfLot) {
      solrSlicedRes.push([]);
    }

    return solrSlicedRes;
  };

  return self;
}
