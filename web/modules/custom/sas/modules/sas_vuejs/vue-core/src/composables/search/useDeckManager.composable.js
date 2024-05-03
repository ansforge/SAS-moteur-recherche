/* eslint-disable */
import {
  ref, computed, watch,
} from 'vue';

/**
 * This composable is used to manage filters of a bigger collections.
 * It is bind to a store collection and update its internal filters based on it
 *
 * @todo This composable does no business logic for now. To have a complete system, we must implement it
 * @param {Object} _
 * @param {import('vue').Ref<Map<string, ICard>>} _.collection - The collection to monitor. This composable must not modify it
 * @param {import('vue').Ref<object>} _.filtersToMonitor - The filters facets used to construct the cardsPerFilters
 */
// eslint-disable-next-line import/prefer-default-export
export function useDeckManager({ collection, currentAppliedFilters, filtersToMonitor }) {
  const self = {};

  /**
   * Array of nid/uid
   * @type {import('vue').Ref<string[]>}
   */
  self.currentDeck = ref([]);

  self.filtersToDisplay = ref({});

  /**
   * An array of cards id. It is reactive since a modification of this array
   * must trigger a recomputation of the filters associated with it
   * @type {import("vue").ComputedRef<string[]>}
   */
  self.cards = computed(() => ([...collection.value.keys()]));

  /**
   * This variable is updated dynamically each time a new card is added
   * OR each time a new filter to monitor is added
   */
  self.cardsPerFilters = ref({});

  watch(self.cards, (newCards) => {
    self.currentDeck.value = [...newCards];
  }, {
    immediate: true,
  });

  return self;
}
