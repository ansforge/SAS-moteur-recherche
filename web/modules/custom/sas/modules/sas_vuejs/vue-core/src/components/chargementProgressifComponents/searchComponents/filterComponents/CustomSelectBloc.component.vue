<template>
  <div class="search-retail__selection">
    <!-- First 2 filters -->
    <template v-for="(filters, idx) in filtersToDisplay">
      <CustomSelectInput
        v-if="filters.isVisible"
        :filtersId="filters.key"
        :filters="filters"
        :isFilterOpen="filters.key === currentOpenedDisplay"
        :isChecked="resetSolrFilters"
        :key="`custom-select-input-${idx}`"
        @update-data="onFilterSelect"
        @custom-filter-toggle="onCustomFilterToggle"
      />
    </template>

    <!-- All filters button -->
    <div v-if="hasVisibleFilters" class="itm_establishment_types-container search-retail__choice">
      <button
        id="all_filters_btn"
        class="search-retail__choice__cta"
        type="button"
        aria-controls="sas-all-filters-modal"
        @click.prevent="openAllFiltersModal"
      >
        Tous les filtres
        <i class="icon-filter" aria-hidden="true" />
      </button>
    </div>

    <!-- All filters modal -->
    <AllFiltersModal
      v-if="showModalFilter"
      :filtersList="filtersList"
      @close-modal="showModalFilter = false"
      @submit-modal-filters="applyCurrentFilters"
      @reset-modal-filters="resetFilters"
    />
  </div>
</template>

<script>
import {
 computed,
 ref,
 onMounted,
 onUnmounted,
 watch,
} from 'vue';

import { useSearchData } from '@/stores';
import CustomSelectInput from './CustomSelectInput.component.vue';
import AllFiltersModal from './AllFiltersModal.component.vue';

export default {
  components: {
    CustomSelectInput,
    AllFiltersModal,
  },
  props: {
    filtersList: {
      type: Array,
      default: () => ([]),
    },
  },
  emits: ['on-filters-change', 'on-filters-submit', 'on-filters-reset'],
  setup(props, { emit }) {
    const searchDataStore = useSearchData();
    const hasVisibleFilters = computed(() => props.filtersList?.some((element) => element.isVisible));
    const currentOpenedDisplay = ref('');
    const selectedFilters = ref({});

    const filtersToDisplay = computed(() => (props.filtersList.length > 2
      ? props.filtersList.slice(0, 2)
      : props.filtersList));

    /**
     * fetch data from children component and set it to store
     * @param {Object} value
     */
    function onFilterSelect(currentData) {
      resetSolrFilters.value = false;
      selectedFilters.value = { ...selectedFilters.value, ...currentData };

      Object.keys(selectedFilters.value).forEach((filterKey) => {
        if (!selectedFilters.value[filterKey].length) {
          delete selectedFilters.value[filterKey];
        }
      });

      emit('on-filters-change', selectedFilters.value);
    }

    function onCustomFilterToggle(filterData) {
      currentOpenedDisplay.value = filterData.isOpen ? filterData.filterCat : '';
    }

    function applyCurrentFilters() {
      emit('on-filters-submit');
      resetSolrFilters.value = false;
      currentOpenedDisplay.value = '';
    }

    // this value could be used to reset solr filters
    const resetSolrFilters = ref(false);
    function resetFilters() {
      emit('on-filters-reset');
      resetSolrFilters.value = false;
      currentOpenedDisplay.value = '';
    }

    function clickedOutside(evt) {
      const current = evt.target;
      const isInsideElement = (
        current?.classList?.contains('search-retail__choice')
        || current?.classList?.contains('search-retail__choice__cta')
        || current?.classList?.contains('filter-selector')
        || current?.classList?.contains('filter-selector-label')
        || current?.parentNode?.classList?.contains('search-retail__choice__cta')
        || current?.parentNode?.classList?.contains('search-retail__choice__option-submit')
      );

      if (!isInsideElement) {
        currentOpenedDisplay.value = '';
      }
    }

    const showModalFilter = ref(false);
    function openAllFiltersModal() {
      showModalFilter.value = true;
      currentOpenedDisplay.value = '';
    }

    onMounted(() => {
      document.addEventListener('click', clickedOutside);
    });

    onUnmounted(() => {
      document.removeEventListener('click', clickedOutside);
    });

    watch(
      () => searchDataStore.currentSelectedFilters,
      (currentFilters) => {
        selectedFilters.value = currentFilters;
      },
    );

    return {
      hasVisibleFilters,
      currentOpenedDisplay,
      selectedFilters,
      onFilterSelect,
      onCustomFilterToggle,
      applyCurrentFilters,
      resetFilters,
      resetSolrFilters,
      filtersToDisplay,
      showModalFilter,
      openAllFiltersModal,
    };
  },
};
</script>
