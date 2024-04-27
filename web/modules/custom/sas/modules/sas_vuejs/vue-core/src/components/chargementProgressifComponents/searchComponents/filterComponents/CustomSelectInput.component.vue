<template>
  <div :class="`${filtersId}-container`" class="search-retail__choice">
    <button
      :id="filtersId"
      :class="showList ? 'select-opened' : 'closed'"
      class="search-retail__choice__cta"
      type="button"
      :aria-expanded="showList"
      :aria-controls="`${filtersId}-list`"
      @click.prevent="handleDisplay"
    >
      {{ filters.label }}
      <span v-if="selectedItem.length" class="search-retail__choice__number">
        {{ selectedItem.length }}
        <span class="sr-only">nombre de filtre(s) sélectionné(s) : {{ selectedItem.length}}</span>
      </span>
      <i class="icon-down" aria-hidden="true" />
    </button>

    <div
      v-if="showList"
      :class="`${filters.key}-select`"
      class="search-retail__choice__wrapper"
    >
      <ul
        v-if="filters?.items.length"
        :id="`${filters.key}-list`"
      >
        <template v-for="(item, idx) in filters.items">
          <li
            v-if="item.isVisible"
            class="search-retail__choice__option"
            :key="`${filtersId}-${idx}`"
          >
            <div class="form-item form-type-checkbox">
              <input
                type="checkbox"
                :id="`${filtersId}-${idx}`"
                class="filter-selector"
                :value="item.idItems"
                v-model="selectedItem"
              />
              <label
                class="filter-selector-label"
                :for="`${filtersId}-${idx}`"
              >
                {{ item.label }}
              </label>
            </div>
          </li>
        </template>

        <!-- apply button -->
        <li class="search-retail__choice__option-submit">
          <div class="form-item form-type-checkbox">
            <button
              type="button"
              class="btn"
              @click.prevent="handleSelectedItem"
            >
              Appliquer
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
  import { ref, watchEffect, watch } from 'vue';
  import { useSearchData } from '@/stores';

  export default {
    props: {
      filtersId: {
        type: String,
        default: '',
      },
      filters: {
        type: Object,
        default: () => ({}),
      },
      isFilterOpen: {
        type: Boolean,
        default: false,
      },
      isChecked: {
        type: Boolean,
        default: false,
      },
    },
    emits: ['update-data', 'custom-filter-toggle'],
    setup(props, { emit }) {
      const showList = ref(props.isFilterOpen);
      const selectedItem = ref([]);
      const searchDataStore = useSearchData();

      function handleDisplay() {
        emit('custom-filter-toggle', {
          filterCat: props.filtersId,
          isOpen: !props.isFilterOpen,
        });
      }

      function handleSelectedItem() {
        emit('update-data', {
          [props.filters.key]: [...selectedItem.value],
        });
      }

      watchEffect(() => {
        showList.value = props.isFilterOpen;
        if (props.isChecked) {
          selectedItem.value = [];
        }

        if (
          !showList.value
          && !props.isChecked
        ) {
          selectedItem.value = searchDataStore.currentSelectedFilters[props.filtersId] || [];
        }
      });

      watch(
        () => searchDataStore.currentSelectedFilters,
        (currentFilters) => {
          selectedItem.value = (currentFilters[props.filtersId])
          ? currentFilters[props.filtersId]
          : [];
        },
      );

      return {
        showList,
        selectedItem,
        handleDisplay,
        handleSelectedItem,
      };
    },
  };
</script>
