<template>
  <div class="search-list">
    <SearchNoResult
      v-if="showNoResultsBloc"
      @change-to-overbooking-filter="$emit('change-to-overbooking-filter')"
    />

    <ul v-else class="search-card-list">
      <li
        v-for="(card, idx) in cardList"
        :id="`search-card-${card.its_nid}`"
        class="search-card-item"
        :class="[
          { 'item-active': card.its_nid === currentHoveringCard },
          { 'large-card': miniMap },
        ]"
        :key="`card-list-item-${idx}`"
        @mouseenter="cardMouseEnter(card)"
        @mouseleave="cardMouseLeave(card)"
      >
        <SearchCard :cardData="card" :cardIndex="idx" :key="`card-item-${card.its_nid}`" :miniMap="miniMap" />
      </li>

      <li v-if="showLoader" class="search-loader">
        <SearchLoading />
      </li>

      <li v-if="showEndOfLoadingMessage" class="search__end__results">
        <div class="search__end__results__container">
          L’ensemble de l’offre de soins avec disponibilité a été proposé.
          {{ SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE }}

          <a
            href="#"
            class="link"
            @click.prevent="$emit('change-to-overbooking-filter')"
          >
            Rechercher en sus des disponibilités
          </a>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
import { computed, ref } from 'vue';

import _isEmpty from 'lodash.isempty';
import { SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE } from '@/const';

import { useSearchData, useUserData } from '@/stores';
import SearchCard from './cardComponents/SearchCard.component.vue';
import SearchNoResult from './SearchNoResult.component.vue';
import SearchLoading from './SearchLoading.component.vue';

export default {
  components: {
    SearchCard,
    SearchNoResult,
    SearchLoading,
  },
  props: {
    cardList: {
      type: Array,
      default: () => ([]),
    },
    miniMap: {
      type: Boolean,
      default: false,
    },
    showLoader: {
      type: Boolean,
      default: false,
    },
    endOfDisplayedList: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    'mouseenter-list-card',
    'mouseleave-list-card',
    'change-to-overbooking-filter',
  ],
  setup(props, { emit }) {
    const currentHoveringCard = ref(null);

    function cardMouseEnter(card) {
      currentHoveringCard.value = card?.its_nid || null;
      emit('mouseenter-list-card', {
        markerId: card.its_nid ? card.its_nid : '',
      });
    }

    function cardMouseLeave(card) {
      currentHoveringCard.value = null;
      emit('mouseleave-list-card', {
        markerId: card.its_nid ? card.its_nid : '',
      });
    }

    const userStore = useUserData();
    const showEndOfLoadingMessage = computed(() => (
      !props.showLoader
      && props.endOfDisplayedList
      && isFiltered.value
      && searchDataStore.currentClusterItems.length === 0
      && userStore.currentUser?.isRegulateurOSNP
    ));

    // No result feature
    const searchDataStore = useSearchData();
    const searchIsLoading = computed(() => searchDataStore.isLoading);
    const isFiltered = computed(() => searchDataStore.isFiltered);
    const showNoResultsBloc = computed(() => (
      !props.cardList?.length
      && !searchIsLoading.value
      && _isEmpty(searchDataStore.currentSelectedFilters)
    ));

    return {
      currentHoveringCard,
      cardMouseEnter,
      cardMouseLeave,
      searchIsLoading,
      showNoResultsBloc,
      isFiltered,
      showEndOfLoadingMessage,
      SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE,
    };
  },
};
</script>
