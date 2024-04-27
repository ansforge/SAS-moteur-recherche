<template>
  <div class="search-page-container">
    <SearchHeader />

    <template v-if="!geolocationHasFailed">
      <div class="search-filters">
        <div class="filters">
          <Filter
            :updateCheckedFilter="updateCheckedFilter"
            @launch-search-custom-filter="launchSearchWithCustomFilters"
            @reset-filters="reLaunchSearch"
          />
        </div>
      </div>

      <ClusterBanner
        v-if="isClusterDisplayed"
        :clusterAddress="clusterAddress"
        @return-to-list="forceResetClusters"
      />

      <TheLegend />
    </template>

    <div id="search-results" class="search-results" :class="{ 'mini-map': isActive }" aria-live="polite">
      <SearchList
        :cardList="cardsToDisplay"
        :miniMap="isActive"
        :showLoader="showLoader"
        :endOfDisplayedList="endOfDisplayedList"
        @mouseenter-list-card="handleMarkerHighlight"
        @mouseleave-list-card="handleMarkerHighlight"
        @change-to-overbooking-filter="onChangeToOverbookingFilter"
      />

      <div class="search-map">
        <button
          v-if="cardsToDisplay.length"
          type="button"
          class="toggle-map"
          @click.prevent="isActive = !isActive"
        >
          <i class="icon" :class="{ 'icon-right': !isActive, 'icon-left': isActive }" :aria-hidden="!isActive" />
        </button>

        <Map
          :currentDisplayedList="cardsToDisplay.map((card) => (card.its_nid))"
          :isPageOne="currentDisplayedLot === 1"
          :isClusterDisplayed="isClusterDisplayed"
          ref="map"
          @research-in-bounds="onMapRelaunchSearch"
          @clicked-map-cluster="onMapClusterClick"
          @clicked-map-marker="onMapMarkerClick"
        />
      </div>
    </div>
  </div>

  <Teleport to=".search-list">
    <Pagination
      v-if="showPagination"
      :current-page="currentDisplayedLot"
      :previous-page="previousPage"
      :next-page="nextPage"
      :total-pages="numberOfPages"
      @update-current-page="updateCurrentDisplayedLot"
    />
  </Teleport>
</template>

<script>
import {
 ref,
 computed,
 onMounted,
 nextTick,
 watch,
} from 'vue';
import { storeToRefs } from 'pinia';

import _isEqual from 'lodash.isequal';
import _isEmpty from 'lodash.isempty';

import SearchHeader from '@/components/chargementProgressifComponents/SearchHeader.component.vue';
import Filter from '@/components/chargementProgressifComponents/searchComponents/filterComponents/Filter.component.vue';
import TheLegend from '@/components/chargementProgressifComponents/TheLegend.component.vue';
import SearchList from '@/components/chargementProgressifComponents/searchComponents/SearchList.component.vue';
import Pagination from '@/components/chargementProgressifComponents/Pagination.component.vue';
import Map from '@/components/chargementProgressifComponents/searchComponents/Map.component.vue';
import ClusterBanner from '@/components/searchComponents/listViewComponents/ClusterBanner.component.vue';

import { FiltersModel } from '@/models';

import {
  useUserData,
  useSasOrientationData,
  useSearchData,
  useLrmData,
  useSearchType,
  useFilterDictionnary,
  useGeolocationData,
} from '@/stores';

import {
  AddressService,
  SearchService,
  SettingService,
  UserService,
} from '@/services';

import {
  useSearchUtils,
  useSearchApiCalls,
  useScroll,
} from '@/composables';

import {
  cookie,
  routeHelper,
} from '@/helpers';

export default {
  components: {
    SearchHeader,
    Filter,
    TheLegend,
    Pagination,
    SearchList,
    Map,
    ClusterBanner,
  },
  setup() {
    const { configureSearchPrefDoctor } = useSearchUtils();
    const { scrollToTop, scrollToElement } = useScroll();
    const { getSOLRresults, getApiResults } = useSearchApiCalls();
    const isActive = ref(false);

    // stores
    const lrmDataStore = useLrmData();
    const sasOrientationDataStore = useSasOrientationData();
    const userDataStore = useUserData();
    const searchDataStore = useSearchData();
    const searchTypeStore = useSearchType();
    const filterDictionnaryStore = useFilterDictionnary();
    const geolocationStore = useGeolocationData();

    const { hasFailed: geolocationHasFailed } = storeToRefs(geolocationStore);

    const nbrPerLot = ref(25);
    const map = ref(null);

    // buffer for SOLR results by packets of 25
    const solrSlicedRes = ref({});
    const solrUnfilteredSlicedRes = ref({});

    // cursor for current SOLR package
    const currentSolrArraySliceWithSlots = ref(1);
    const currentSolrArraySliceWithoutSlots = ref(1);

    // starts at 1
    const currentDisplayedLot = ref(1);
    const cardsToDisplay = computed(() => {
      const currentLot = Math.max(0, currentDisplayedLot.value - 1);

      return searchDataStore.getCurrentLot(currentLot);
    });
    const endOfDisplayedList = computed(() => (
      !searchDataStore.isLoading
      && searchDataStore.currentList?.length === currentDisplayedLot.value
    ));

    const showLoader = computed(() => (
      searchDataStore.isLoading
      && (cardsToDisplay.value.length < 5 || !showPagination.value)
    ));

    // keep facets to verify if any changement occurs
    const currentFilterFacets = ref({
      filtered: {},
      unfiltered: {},
    });

    /**
     * set filter facets for a given search
     * @param {Object} solrRes
     * @param {Boolean} customFilterSearch
     */
    function setFilterFacetsInStore(solrRes, customFilterSearch = false) {
      let currentFacets = !customFilterSearch
      ? currentFilterFacets.value.filtered
      : currentFilterFacets.value.unfiltered;

      if (!_isEqual(currentFacets, solrRes.data?.facet_counts?.facet_fields)) {
        currentFacets = solrRes.data?.facet_counts?.facet_fields || {};

        // add sas custom filters
        if (!currentFacets.itm_establishment_types) {
          currentFacets.itm_establishment_types = {};
        }
        currentFacets.itm_establishment_types['222605'] = 1;

        const currentFilters = new FiltersModel(currentFacets);

        if (!customFilterSearch) {
          currentFilterFacets.value.filtered = currentFacets;
          searchDataStore.setFiltersWithSlot(currentFilters.filters);
        } else {
          currentFilterFacets.value.unfiltered = currentFacets;
          searchDataStore.setFiltersWithoutSlot(currentFilters.filters);
        }
      }

      prepareSolrBreakdownToLots(solrRes, !customFilterSearch);
    }

    const hasEnoughSolrResults = ref(true);
    /**
     * verify whether the search should stop or continue calling api (SOLR/sas api/agreg)
     * and set loading status
     * @param {Array} solrRes
     * @param {Boolean} isStillLoading
     */
    async function setSearchEndStatus(solrRes) {
      hasEnoughSolrResults.value = hasEnoughDocList(solrRes, searchDataStore.isFiltered);

      if (shouldGetMoreResults.value) {
        // update solr call page
        if (searchDataStore.isFiltered) {
          searchDataStore.paginationData.setPaginationPage(searchDataStore.paginationData.page + 1);
        } else {
          searchDataStore.paginationDataUnfiltered.setPaginationPage(searchDataStore.paginationDataUnfiltered.page + 1);
        }

        await fetchBatchSolr(!searchDataStore.isFiltered);
      } else {
        searchDataStore.isLoading = false;
        searchDataStore.updateFilters();
      }
    }

    /**
     * fetch data from SOLR with given filters
     */
    async function fetchBatchSolr(customFilterSearch = false) {
      const promiseSearch = [];
      let unfilteredSolr = {};

      promiseSearch.push(
        (!searchTypeStore.isSearchStructure && !customFilterSearch)
        ? getSOLRresults()
        : Promise.resolve(),
      );

      if (
        searchDataStore.isFirstLoad
        || searchTypeStore.isSearchStructure
        || customFilterSearch
      ) {
        promiseSearch.push(getSOLRresults(false));
      }

      const promiseAllRes = await Promise.all(promiseSearch);
      const solrRes = promiseAllRes[0] || {};

      // set filters only if facets change filtered
      if (!customFilterSearch) {
        setFilterFacetsInStore(solrRes, false);

        // add pref doctor into filter lists
        if (
          searchDataStore.isFirstLoad
          && lrmDataStore.displayPreferredDoctor
          && lrmDataStore.preferredDoctorData?.length
        ) {
          searchDataStore.sortElementsIntoFilters(lrmDataStore.preferredDoctorData);
        }
      }

      if (
        searchDataStore.isFirstLoad
        || searchTypeStore.isSearchStructure
        || customFilterSearch
      ) {
        unfilteredSolr = promiseAllRes[1] || {};

        // set filters only if facets change unfiltered
        setFilterFacetsInStore(unfilteredSolr, true);

        await fetchBatchApiUnfiltered(
          !searchDataStore.isFirstLoad
          || (!currentUser.value?.isRegulateurOSNP && currentUser.value?.isRegulateurIOA),
        );
        searchDataStore.isFirstLoad = false;
      }

      await fetchBatchApi();

      nextTick(async () => {
        await setSearchEndStatus(searchDataStore.isFiltered ? solrRes : unfilteredSolr);
      });
    }

    // detects if more results are needed
    const shouldGetMoreResults = computed(() => {
      const currentElements = searchDataStore.currentList.reduce((acc, lot) => acc.concat(lot), []);

      // always get results for page n & n+1
      const expectedNbrResults = (currentDisplayedLot.value * 5) + 5;
      return (currentElements.length < expectedNbrResults && hasEnoughSolrResults.value);
    });

    /**
     * SOLR has 100 results
     * @param {Arrray} res
     * @param {Boolean} isFilteredList
     */
    function hasEnoughDocList(res, isFilteredList) {
      const currentList = res?.data?.grouped?.ss_field_custom_group?.groups.map((group) => group?.doclist?.docs[0]) || [];

      return (
        (currentList.length === 100 && isFilteredList)
        || (currentList.length === 25 && !isFilteredList)
      );
    }

    /**
     * Create & split solr results array in batches
     * @param {*} res
     */
    function prepareSolrBreakdownToLots(res, isFiltered = true) {
      const resUnfiltered = res?.data?.grouped?.ss_field_custom_group?.groups.map((group) => group?.doclist?.docs[0]) || [];
      const nbLots = Math.floor(resUnfiltered.length / nbrPerLot.value) + (resUnfiltered.length % nbrPerLot.value > 0 ? 1 : 0);

      if (resUnfiltered.length !== 0) {
        if (isFiltered) {
          new Array(nbLots).fill(0).forEach(async (val, idx) => {
            const sliceIdx = (idx + 1);
            const sliceStart = idx * nbrPerLot.value;
            const sliceEnd = (sliceIdx * nbrPerLot.value);
            const sliceLimit = sliceEnd > resUnfiltered.length ? resUnfiltered.length : sliceEnd;
            const currentResults = resUnfiltered.slice(sliceStart, sliceLimit);
            solrSlicedRes.value[sliceIdx] = currentResults;
          });
        } else {
          solrUnfilteredSlicedRes.value[currentSolrArraySliceWithoutSlots.value] = resUnfiltered;
        }
      }
    }

    /**
     * Launch sas api && agreg to get at least 5 cards
     */
    async function fetchBatchApi() {
      // set aggregator token
      if (!cookie.getCookie('sas_aggregator_token')) {
        await SettingService.getAggregatorToken();
      }

      if (
        solrSlicedRes.value[currentSolrArraySliceWithSlots.value]
      ) {
        const results = await getApiResults(solrSlicedRes.value[currentSolrArraySliceWithSlots.value]);
        const resultsWithSlots = results.filter((res) => (
          res.slotList
          && (
            res.slotList.today.length > 0
            || res.slotList.tomorrow.length > 0
            || res.slotList.afterTomorrow.length > 0
          )
        ));
        searchDataStore.setAllResults(resultsWithSlots, true);
        currentSolrArraySliceWithSlots.value += 1;

        if (shouldGetMoreResults.value) {
          await fetchBatchApi();
        }

        if (!solrSlicedRes.value[currentSolrArraySliceWithSlots.value]) {
          currentSolrArraySliceWithSlots.value = 1;
          solrSlicedRes.value = {};
        }
      }
    }

    /**
    * Launch sas api && agreg for unfiltered result
    */
    async function fetchBatchApiUnfiltered(setResults = true) {
      if (
        solrUnfilteredSlicedRes.value[currentSolrArraySliceWithoutSlots.value]
      ) {
        const results = await getApiResults(solrUnfilteredSlicedRes.value[currentSolrArraySliceWithoutSlots.value], false);

        if (setResults) {
          searchDataStore.setAllResults(results, false);
        }
      }
    }

    // Pagination feature
    const numberOfPages = computed(() => searchDataStore.currentList.length);
    const showPagination = computed(() => (numberOfPages.value >= currentDisplayedLot.value) && numberOfPages.value > 1);
    const previousPage = computed(() => currentDisplayedLot.value - 1);
    const nextPage = computed(() => currentDisplayedLot.value + 1);

    function updateCurrentDisplayedLot(newCurrentPage) {
      currentDisplayedLot.value = newCurrentPage;

      const searchWrapper = document.getElementById('sas-search-wrapper');
      scrollToElement(searchWrapper);

      if (shouldGetMoreResults.value) {
        nextTick(async () => {
          await completeResults();
        });
      }
    }

    // if not enough results fetch more
    async function completeResults() {
      searchDataStore.isLoading = true;

      if (
        searchDataStore.isFiltered
        && solrSlicedRes.value[currentSolrArraySliceWithSlots.value]
      ) {
        await fetchBatchApi();
      }

      if (shouldGetMoreResults.value && hasEnoughSolrResults.value) {
        if (searchDataStore.isFiltered) {
          searchDataStore.paginationData.setPaginationPage(searchDataStore.paginationData.page + 1);
        } else {
          searchDataStore.paginationDataUnfiltered.setPaginationPage(searchDataStore.paginationDataUnfiltered.page + 1);
        }

        await fetchBatchSolr(!searchDataStore.isFiltered);
      } else {
        searchDataStore.isLoading = false;
      }
    }

    const currentUser = computed(() => userDataStore.currentUser);

    onMounted(async () => {
      if (geolocationHasFailed.value) {
        searchDataStore.isLoading = false;
        return;
      }

      nextTick(() => {
        const circleHasBeenDrawn = map.value.drawCircle({
          latitude: geolocationStore.geolocation.latitude,
          longitude: geolocationStore.geolocation.longitude,
          radius: geolocationStore.geolocation.defaultRadius,
        });

        map.value.fitBoundsToCircle();

        if (!circleHasBeenDrawn) {
          console.error("The map isn't available yet to draw the circle");
        }
      });

      cookie.removeCookie('sas_aggregator_token');
      await initialize();
    });

    async function initialize() {
      // init all data before search
      await fetchAndApplyConfiguration();

      // init data if pref Dr or precise search
      await configureSearchPrefDoctor();

      adaptDisplayToCurrentUser();

      const hasCustomFilters = !_isEmpty(searchDataStore.customFilters);

      // launch search
      await fetchBatchSolr(hasCustomFilters);
    }

    /**
     * This function must be called first for this page to behave correctly.
     * It:
     *  - Initializes the LRM store
     *  - Retrieves the current user
     *  - Aggregates the settings with miscellaneous labels
     *  - Fetches a new aggregator token if the previous one is expired
     */
    async function fetchAndApplyConfiguration() {
      // LRM search config
      await lrmDataStore.setSpeciality();
      lrmDataStore.setPrefDoctorParam();

      const configurationPromise = [];

      configurationPromise.push(UserService.getCurrentUser());
      configurationPromise.push(SearchService.getStructureMapping(routeHelper.getUrlParam('text')));
      configurationPromise.push(SettingService.getSasApiSettingsByParam('sas_participation'));
      configurationPromise.push(SettingService.getSasApiSettingsByParam('reorientation'));
      configurationPromise.push(SettingService.getSasApiSettingsByParam('popin_snp'));
      configurationPromise.push(SettingService.getSasApiSettingsByParam('orientation_general'));
      configurationPromise.push(SettingService.getDictionaryFilter());

      // set aggregator token
      if (!cookie.getCookie('sas_aggregator_token')) {
        configurationPromise.push(SettingService.getAggregatorToken());
      }

      const configurationResponse = await Promise.all(configurationPromise);

      // set user data
      userDataStore.setCurrentUser(configurationResponse[0]);

      // set if search is structure
      searchTypeStore.setIsSearchStructure(configurationResponse[1].isStructureSearch);

      // set all orientation datas
      sasOrientationDataStore.setSasParticipationSettings(configurationResponse[2]);
      sasOrientationDataStore.setReorientationSettings(configurationResponse[3]);
      sasOrientationDataStore.setPopinSnpSettings(configurationResponse[4] ? configurationResponse[4].value : {});
      sasOrientationDataStore.setOrientationSettings(configurationResponse[5]);

      // set filter labels
      filterDictionnaryStore.setFilterTypeLabels(configurationResponse[6]);
    }

    function adaptDisplayToCurrentUser() {
      const isUserIOAonly = (currentUser.value?.isRegulateurIOA && !currentUser.value?.isRegulateurOSNP);
      if (searchTypeStore.isSearchStructure || isUserIOAonly) {
        searchDataStore.setCurrentDisplayedList('unfiltered');
      }

      if (isUserIOAonly) {
        searchDataStore.setCustomFilters({ bs_sas_forfait_reo: [true] });
        searchDataStore.defaultCustomFilter = 'bs_sas_forfait_reo';
        searchDataStore.updateCustomFilterValue('has_slot', { isSelected: false, isVisible: false });
        searchDataStore.updateCustomFilterValue('bs_sas_overbooking', { isSelected: false, isVisible: false });
        searchDataStore.updateCustomFilterValue('bs_sas_forfait_reo', { isSelected: true, isVisible: true });
      } else {
        searchDataStore.updateCustomFilterValue('has_slot', { isSelected: true, isVisible: true });

        if (currentUser.value?.isRegulateurOSNP) {
          searchDataStore.updateCustomFilterValue('bs_sas_overbooking', { isSelected: false, isVisible: true });
        }

        if (currentUser.value?.isRegulateurIOA) {
          searchDataStore.updateCustomFilterValue('bs_sas_forfait_reo', { isSelected: false, isVisible: true });
        }
      }
    }

    /**
     * launch search with custom SAS filters
     * @param {Object} evtData
     */
    async function launchSearchWithCustomFilters(evtData) {
      currentDisplayedLot.value = 1;

      if (Object.values(evtData).length) {
        nextTick(async () => {
          searchDataStore.isLoading = true;
          solrUnfilteredSlicedRes.value = {};
          currentSolrArraySliceWithoutSlots.value = 1;
          searchDataStore.setCurrentDisplayedList('unfiltered');
          updateCheckedFilter.value = false;
          await fetchBatchSolr(true);
        });
      } else {
        searchDataStore.setCustomFilters({});
        searchDataStore.setCurrentDisplayedList('filtered');

        if (
          !isResultsReset.value
          && shouldGetMoreResults.value
        ) {
          await completeResults();
        }
      }
    }

    /**
     * reset all
     */
    async function reLaunchSearch() {
      searchDataStore.resetFilters();
      currentDisplayedLot.value = 1;
      searchDataStore.updateCurrentList();
    }

    /**
     * resets elements of a given search
     * @param {Boolean} isFiltered
     */
    function resetSearchElements(isFiltered = false) {
      if (isFiltered) {
        solrSlicedRes.value = {};
        currentSolrArraySliceWithSlots.value = 1;
        searchDataStore.allResultsWithSlots = [];
        searchDataStore.filtersWithSlot = [];
        currentFilterFacets.value.filtered = {};
        searchDataStore.listNidWithSlots.clear();
        searchDataStore.resultsWithSlotsPerFilter = {};
        searchDataStore.paginationData.setPaginationPage(1);
      } else {
        solrUnfilteredSlicedRes.value = {};
        currentSolrArraySliceWithoutSlots.value = 1;
        searchDataStore.allResultsWithoutSlots = [];
        searchDataStore.filtersWithoutSlot = [];
        currentFilterFacets.value.unfiltered = {};
        searchDataStore.listNidWithoutSlots.clear();
        searchDataStore.resultsWithoutSlotsPerFilter = {};
        searchDataStore.paginationDataUnfiltered.setPaginationPage(1);
      }
    }

    /**
     * launch search from map
     */
    function onMapRelaunchSearch(payload) {
      const geolocationHadFailed = geolocationHasFailed.value;
      if (geolocationHadFailed) {
        geolocationStore.geolocation = {};
      }

      searchDataStore.setCurrentClusterItems([]);

      // We clear both searches
      resetSearchElements(true);
      resetSearchElements(false);

      searchDataStore.resetFilters();
      currentDisplayedLot.value = 1;
      searchDataStore.updateCurrentList();
      searchDataStore.isLoading = true;

      nextTick(async () => {
        scrollToTop();

        // Updates the geolocation store object according to new coordinates
        const {
          city,
          context,
          street,
          citycode: inseeCode,
          housenumber: houseNumber,
          postcode: postCode,
        } = await AddressService.fetchPropertiesByCoordinates(
          payload.longitude,
          payload.latitude,
        ) || {};

        const [countyCode, countyName] = context?.split(', ') || ['', ''];

        geolocationStore.geolocation = {
          ...geolocationStore.geolocation,
          ...payload,
          type: geolocationStore.GEOLOCATION_TYPE.CITY,
          city,
          countyCode,
          countyName,
          houseNumber,
          inseeCode,
          postCode,
          street,
        };
        delete geolocationStore.geolocation.fullAddress;

        map.value.deleteBluePointLayer();

        if (geolocationHadFailed) {
          await initialize();
        }

        await fetchBatchSolr(!searchDataStore.isFiltered);
      });
    }

    const idxBeforeCluster = ref(1);
    const isClusterDisplayed = computed(() => searchDataStore.currentClusterItems.length > 0);
    const clusterAddress = computed(() => ((
      isClusterDisplayed.value
      && cardsToDisplay.value[0]?.ss_field_address
    ) ? cardsToDisplay.value[0].ss_field_address : ''));

    /**
     * handle cluster click
     * @param {Object} evt
     */
    function onMapClusterClick(evt) {
      idxBeforeCluster.value = isClusterDisplayed.value
      ? idxBeforeCluster.value
      : currentDisplayedLot.value;

      const cardIds = evt.properties?.id?.split('-').map((currId) => parseInt(currId, 10));
      currentDisplayedLot.value = isClusterDisplayed.value
      ? idxBeforeCluster.value
      : 1;

      searchDataStore.setCurrentClusterItems(cardIds);

      nextTick(() => {
        const clusterBanner = document.getElementById('reset-cluster');
        scrollToElement(clusterBanner);
      });
    }

    function forceResetClusters() {
      currentDisplayedLot.value = idxBeforeCluster.value;
      searchDataStore.setCurrentClusterItems([]);

      nextTick(scrollToTop);
    }

    /**
     * highlight card from list
     * @param {Object} elemData
     */
    function highlightCard(elemData) {
      const elemId = elemData.elemId ? elemData.elemId : '';
      const card = document.getElementById(`search-card-${elemId}`);
      removeHighlight();
      // eslint-disable-next-line no-unused-expressions
      card?.classList?.add('item-active');
    }

    /**
     * Call the function to child component to active single marker on mouseenter in a card
     * @param markerId
     */
    function handleMarkerHighlight(markerId) {
      map.value.handleCardHover(markerId);
    }

    /**
     * on marker focusout remove card highlight
     * @param markerData
     */
    function removeHighlight() {
      document.querySelectorAll("[class*='search-card-item']").forEach((el) => {
        // eslint-disable-next-line no-unused-expressions
        el?.classList?.remove('item-active');
      });
    }

    /**
     * on marker click highlight card
     * @param markerData
     */
    function onMapMarkerClick(markerData) {
      const highlightedCard = markerData?.content?.properties?.id || '';
      const cardLotIndex = searchDataStore.currentList.findIndex((lot) => {
        const hasCard = lot.findIndex((card) => card?.its_nid === highlightedCard);
        return hasCard > -1;
      });

      if (cardLotIndex > -1) {
        currentDisplayedLot.value = cardLotIndex + 1;
      }

      nextTick(() => {
        const card = document.getElementById(`search-card-${highlightedCard}`);
        scrollToElement(card);
        highlightCard(markerData);
      });
    }

    // No result feature
    const updateCheckedFilter = ref(false);
    function onChangeToOverbookingFilter() {
      searchDataStore.setCustomFilters({ bs_sas_overbooking: [true] });
      searchDataStore.defaultCustomFilter = 'bs_sas_overbooking';
      updateCheckedFilter.value = true;

      nextTick(scrollToTop);
    }

    // on filter change go to page 1
    watch(
      () => searchDataStore.currentSelectedFilters,
      () => {
        currentDisplayedLot.value = 1;

        nextTick(async () => {
          if (
            !searchDataStore.isLoading
            && shouldGetMoreResults.value
            && !isResultsReset.value
          ) {
            await completeResults();
          }
        });
      },
    );

    // on timeout(2min) reset filtered search
    let filteredListTimeoutId = null;
    const isResultsReset = ref(false);
    watch(
    () => searchDataStore.customFilters,
      async (newVal) => {
        if (_isEmpty(newVal)) {
          clearTimeout(filteredListTimeoutId);

          if (
            searchDataStore.allResultsWithSlots?.length === 0
            && searchDataStore.filtersWithSlot?.length === 0
            && searchDataStore.listNidWithSlots?.size === 0
          ) {
            searchDataStore.isLoading = true;
            await fetchBatchSolr();
            isResultsReset.value = false;
          }
        } else {
          filteredListTimeoutId = setTimeout(() => {
            isResultsReset.value = true;
            resetSearchElements(true);
          }, 120000);
        }
      },
    );

    return {
      map,
      currentDisplayedLot,
      cardsToDisplay,
      numberOfPages,
      showPagination,
      previousPage,
      nextPage,
      isActive,
      shouldGetMoreResults,
      updateCurrentDisplayedLot,
      launchSearchWithCustomFilters,
      reLaunchSearch,
      onMapRelaunchSearch,
      onMapClusterClick,
      onMapMarkerClick,
      handleMarkerHighlight,
      removeHighlight,
      highlightCard,
      isClusterDisplayed,
      clusterAddress,
      forceResetClusters,
      updateCheckedFilter,
      showLoader,
      endOfDisplayedList,
      onChangeToOverbookingFilter,
      currentFilterFacets,
      geolocationHasFailed,
      hasEnoughSolrResults,
    };
  },
};
</script>
