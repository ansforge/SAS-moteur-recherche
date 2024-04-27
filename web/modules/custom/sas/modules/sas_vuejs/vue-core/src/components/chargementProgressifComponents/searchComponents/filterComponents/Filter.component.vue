<template>
  <div class="search-retail" aria-live="polite">
    <!-- Participation & en sus des disponibilités & FRU -->
    <CustomRadioInput
      :radio-input-config="radioInputConfig"
      :default-value="defaultRadioVal"
      @on-filter-change="onCustomFilterChange"
    />

    <!-- BLOC Selects & Buttons -->
    <CustomSelectBloc
      v-if="hasVisibleFilters"
      :filters-list="filtersList"
      @on-filters-change="onFiltersChange"
      @on-filters-submit="onFiltersSubmit"
      @on-filters-reset="onFiltersReset"
    />
  </div>
</template>

<script>
import { computed, watch } from 'vue';

import { useSearchData } from '@/stores';

import { useScroll } from '@/composables';

import CustomSelectBloc from './CustomSelectBloc.component.vue';
import CustomRadioInput from './CustomRadioInput.component.vue';

export default {
  components: {
    CustomSelectBloc,
    CustomRadioInput,
  },
  props: {
    updateCheckedFilter: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['launch-search-custom-filter', 'reset-filters'],
  setup(props, { emit }) {
    const searchDataStore = useSearchData();
    const filtersList = computed(() => searchDataStore.getCurrentFilters);
    const hasVisibleFilters = computed(() => filtersList.value?.some((element) => element?.isVisible));

    // custom filters (versus filters from solR result)
    const radioInputConfig = computed(() => Object.values(searchDataStore.customFiltersToDisplay));

    const defaultRadioVal = computed(() => searchDataStore.defaultCustomFilter);

    function onCustomFilterChange(currentRadioValue) {
      const evtData = {};

      searchDataStore.defaultCustomFilter = currentRadioValue;
      if (currentRadioValue !== 'has_slot') {
        evtData[currentRadioValue] = [true];
        searchDataStore.isLoading = true;
        searchDataStore.paginationDataUnfiltered.setPaginationPage(1);
        searchDataStore.allResultsWithoutSlots = [];
        searchDataStore.listNidWithoutSlots.clear();
        searchDataStore.setCustomFilters(evtData);
      }

      searchDataStore.setCurrentClusterItems([]);
      emit('launch-search-custom-filter', evtData);
    }

    function onFiltersChange(selectedFilters) {
      searchDataStore.setCurrentSelectedFilters(selectedFilters);
      onFiltersSubmit();
    }

    function onFiltersSubmit() {
      searchDataStore.updateCurrentList();
    }

    function onFiltersReset() {
      emit('reset-filters');
    }

    const { scrollToElement } = useScroll();
    watch(() => props.updateCheckedFilter, (newVal) => {
      if (newVal) {
        onCustomFilterChange('bs_sas_overbooking');
        const searchListElem = document.getElementById('sas-search-wrapper');
        scrollToElement(searchListElem);
      }
    });

    return {
      radioInputConfig,
      filtersList,
      defaultRadioVal,
      hasVisibleFilters,
      onCustomFilterChange,
      onFiltersChange,
      onFiltersSubmit,
      onFiltersReset,
    };
  },
};
</script>
