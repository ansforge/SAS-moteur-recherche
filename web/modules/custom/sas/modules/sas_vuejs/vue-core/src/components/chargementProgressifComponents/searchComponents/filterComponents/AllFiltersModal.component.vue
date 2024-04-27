<template>
  <ModalWrapper
    :title="'TOUS LES FILTRES'"
    :modalId="'sas-all-filters-modal'"
    modal-class="modal-all-filters"
    @on-close-modal="$emit('close-modal')"
  >
    <div class="all__filters__container">
      <template v-for="(filters, idx) in filtersList">
        <div
          v-if="filters.isVisible"
          :class="`${filters.key}-wrapper`"
          class="all__filters__group"
          :key="`${filters.key}-wrapper-${idx}`"
        >
          <h3>{{ filters.label }}</h3>

          <ul
            v-if="filters?.items.length"
            :id="`${filters.key}-list`"
            class="resetul all__filters__group-items"
          >
            <template v-for="(item, idx) in filters.items">
              <li
                v-if="item.isVisible"
                class="all__filters__group-item"
                :key="`filter-${item.idItems}-group`"
              >
                <div class="form-item form-type-checkbox">
                  <input
                    type="checkbox"
                    :id="`filter-${item.idItems}-item-${idx}`"
                    class="filter-selector"
                    :value="{ item: item.idItems, key: filters.key }"
                    v-model="selectedItem"
                  />
                  <label
                    class="filter-selector-label"
                    :for="`filter-${item.idItems}-item-${idx}`"
                  >
                    {{ item.label }}
                  </label>
                </div>
              </li>
            </template>
          </ul>
        </div>
      </template>

      <!-- Submit & Reset -->
      <div v-if="hasVisibleFilters" class="wrapper-btn-actions">
        <div class="all__filters__actions">
          <div class="btn-container">
            <button
              type="button"
              class="btn outline-blue reset-btn"
              @click.prevent="resetFilters"
            >
              Réinitialiser
            </button>

            <button
              type="button"
              class="btn submit-btn"
              @click.prevent="applyFilters"
            >
              Appliquer
            </button>
          </div>
        </div>
      </div>
    </div>
  </ModalWrapper>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

import _isEmpty from 'lodash.isempty';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';

import { useSearchData } from '@/stores';

export default {
  components: { ModalWrapper },
  props: {
    filtersList: {
      type: Array,
      default: () => ([]),
    },
    selectedFilters: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: [
    'close-modal',
    'submit-modal-filters',
    'reset-modal-filters',
  ],
  setup(props, { emit }) {
    const selectedItem = ref([]);
    const hasVisibleFilters = computed(() => props.filtersList?.some((element) => element.isVisible));
    const searchDataStore = useSearchData();

    function applyFilters() {
      const formattedFilters = {};

      selectedItem.value.forEach((filter) => {
        if (!formattedFilters[filter.key]) {
          formattedFilters[filter.key] = [];
        }

        formattedFilters[filter.key].push(filter.item);
      });

      searchDataStore.setCurrentSelectedFilters(formattedFilters);
      emit('submit-modal-filters', formattedFilters);
      emit('close-modal');
    }

    function resetFilters() {
      selectedItem.value = [];
      emit('reset-modal-filters');
      emit('close-modal');
    }

    onMounted(() => {
      if (!_isEmpty(searchDataStore.currentSelectedFilters)) {
        selectedItem.value = [];
        Object.entries(searchDataStore.currentSelectedFilters).forEach(([itemKey, itemValue]) => {
          selectedItem.value = [...selectedItem.value, ...itemValue.map((item) => ({ item, key: itemKey }))];
        });
      }
    });

    return {
      selectedItem,
      hasVisibleFilters,
      applyFilters,
      resetFilters,
    };
  },
};
</script>
