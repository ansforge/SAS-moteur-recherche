<template>
  <SearchCardList
    :cardList="currentSM.cardsToDisplay.value"
    :miniMap
    :show="showOptions"
    @mouseenter-list-card="onMouseEnterCard"
    @mouseleave-list-card="onMouseLeaveCard"
    @change-to-overbooking-filter="$emit('change-to-overbooking-filter')"
  />

  <Teleport to=".search-list">
    <Pagination
      v-if="showPagination"
      :adapter="SearchPaginationAdapter"
      :actions="currentSM.pageActions"
      :currentLotNumber="currentSM.currentLotNumber.value"
      :numberOfLots="currentSM.numberOfLots.value"
      @go-to-lot="scrollToTop"
    />
  </Teleport>
</template>

<script>
import _isEmpty from 'lodash.isempty';

import {
  computed, watch, provide,
} from 'vue';

import SearchCardList from '@/components/search/SearchCardList.component.vue';
import Pagination from '@/components/sharedComponents/Pagination.component.vue';

import {
  useSearchManager,
  useCptsEffectorsCollectionBaker,
  useScroll,
} from '@/composables';

import { SEARCH_STATUS } from '@/const';

import { SearchPaginationAdapter } from '@/models';

import {
  useCpts,
  useGeolocationData,
  useSearchData,
 } from '@/stores';

export default {
  components: {
    SearchCardList,
    Pagination,
  },
  props: {
    miniMap: {
      type: Boolean,
      default: false,
    },
  },
  // temporary emits
  emits: [
    'cpts-highlighted-pins-id',
    'cpts-all-pins-to-display',
    'mouseenter-list-card',
    'mouseleave-list-card',
    'change-to-overbooking-filter',
  ],
  setup(props, { emit }) {
    const numberOfCardPerPage = 5;

    const { scrollToTop } = useScroll();

    const cptsStore = useCpts();
    const currentCpts = computed(() => cptsStore.currentSelectedCpts);

    const searchDataStore = useSearchData();
    const withSlotSearch = computed(() => _isEmpty(searchDataStore.customFilters));

    const searchManager = useSearchManager({
      collectionId: `cpts-${currentCpts.value?.ss_field_identifiant_finess}-filtered`,
      useCollectionBaker: useCptsEffectorsCollectionBaker,
      numberOfCardPerPage,
    });

    const searchManagerUnfiltered = useSearchManager({
      collectionId: `cpts-${currentCpts.value?.ss_field_identifiant_finess}-unfiltered`,
      useCollectionBaker: useCptsEffectorsCollectionBaker,
      numberOfCardPerPage,
    });

    const currentSM = computed(() => (withSlotSearch.value ? searchManager : searchManagerUnfiltered));

    const showPagination = computed(() => (currentSM.value.numberOfLots.value > 1));
    const showLoader = computed(() => currentSM.value.currentStatus.value === SEARCH_STATUS.NOT_STARTED
      || (currentSM.value.currentStatus.value === SEARCH_STATUS.LOADING && currentSM.value.cardsToDisplay.value.length < numberOfCardPerPage));
    const showEndOfSearchMessage = computed(() => (
      currentSM.value.currentStatus.value === SEARCH_STATUS.FINISHED
        && currentSM.value.currentLotNumber.value === currentSM.value.numberOfLots.value
        && withSlotSearch.value
    ));
    const showNoResult = computed(() => (
      currentSM.value.currentDeck.value.length === 0
        && currentSM.value.currentStatus.value === SEARCH_STATUS.FINISHED
        && _isEmpty(currentSM.value.currentAppliedFilters.value)
    ));

    const showOptions = computed(() => ({
      showLoader: showLoader.value,
      showEndOfSearchMessage: showEndOfSearchMessage.value,
      showNoResult: showNoResult.value,
    }));

    const geolocationStore = useGeolocationData();

    watch(currentSM, () => {
      currentSM.value.openTheBakery({
        withSlot: withSlotSearch.value,
        isPrecise: geolocationStore.geolocation?.type === geolocationStore.GEOLOCATION_TYPE.ADDRESS,
        cptsCard: currentCpts.value,
      });
    }, { immediate: true });

    watch(() => currentSM.value.pinsToDisplay, (pinsToDisplay) => {
      emit('cpts-all-pins-to-display', pinsToDisplay?.value);
    }, {
      deep: true,
      immediate: true,
    });

    watch(() => currentSM.value.cardsToDisplay, (cardsToDisplay) => {
      emit('cpts-highlighted-pins-id', cardsToDisplay?.value.map((card) => card.its_nid));
    }, {
      deep: true,
      immediate: true,
    });

    function onMouseEnterCard(markerId) {
      emit('mouseenter-list-card', markerId);
    }
    function onMouseLeaveCard(markerId) {
      emit('mouseleave-list-card', markerId);
    }

    function gotoPageWithCardOfNid(nid) {
      const cardPage = currentSM.value.getPageWhereCardOfNidIs(nid);
      if (cardPage === -1) {
        console.warn('Page not found for nid:', nid);
        return;
      }
      currentSM.value.pageActions.goToLotOfNumber(cardPage);
    }

    /**
     * To be used wisely. Here it is for each Slot to have direct access to the collection
     * It's not secure as each sub component has the ability to mess with the collection,
     * but it's the easiest solution we have now
     */
    provide('collectionId', currentSM.value.collectionId);

    return {
      currentCpts,

      SearchPaginationAdapter,

      currentSM,

      showPagination,
      showOptions,

      onMouseEnterCard,
      onMouseLeaveCard,
      gotoPageWithCardOfNid,
      scrollToTop,

      // for debug purposes
      withSlotSearch,
    };
  },
};

</script>
