<template>
  <div class="search-list">
    <SearchNoResult
      v-if="show?.showNoResultsBloc"
      @change-to-overbooking-filter="$emit('change-to-overbooking-filter')"
    />

    <ul v-else class="search-card-list">
      <li
        v-for="(card, idx) in cardList"
        :id="`search-card-${card.its_nid}`"
        class="search-card-item"
        :class="[
          { 'item-active': card.its_nid === currentHoveredCardId },
          { 'large-card': miniMap },
        ]"
        :key="`card-list-item-${idx}`"
        @mouseenter="onMouseEnterCard(card)"
        @mouseleave="onMouseLeaveCard(card)"
      >
        <SearchCard
          :cardData="card"
          :cardIndex="idx"
          :miniMap
          :key="`card-item-${card.its_nid}`"
        />
      </li>

      <li v-if="show?.showLoader" class="search-loader">
        <SearchLoading />
      </li>

      <li v-if="show?.showEndOfSearchMessage" class="search__end__results">
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
import { ref } from 'vue';

import SearchCard from '@/components/chargementProgressifComponents/searchComponents/cardComponents/SearchCard.component.vue';
import SearchNoResult from '@/components/chargementProgressifComponents/searchComponents/SearchNoResult.component.vue';
import SearchLoading from '@/components/chargementProgressifComponents/searchComponents/SearchLoading.component.vue';

import { SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE } from '@/const';

/**
 * @typedef {Object} ShowOptions
 * @property {boolean} showLoader
 * @property {boolean} showNoResult
 * @property {boolean} showEndOfSearchMessage
 */

/**
 * @typedef {Object} Props
 * @property {import('@/types').ICard[]} cardList
 * @property {boolean} miniMap
 * @property {ShowOptions} show
 */

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
    show: {
      type: Object,
      default: () => ({}),
    },
  },
  emits: [
    'mouseenter-list-card',
    'mouseleave-list-card',
    'change-to-overbooking-filter',
  ],
  setup(/** @type {Props} */props, { emit }) {
    /** @type {import('vue').Ref<string | null>} */
    const currentHoveredCardId = ref(null);

    /**
     * @param {import('@/types').ICard} card
     */
    function onMouseEnterCard(card) {
      currentHoveredCardId.value = card?.its_nid || null;
      emit('mouseenter-list-card', {
        markerId: card.its_nid ?? '',
      });
    }

    /**
     * @param {import('@/types').ICard} card
     */
    function onMouseLeaveCard(card) {
      currentHoveredCardId.value = null;
      emit('mouseleave-list-card', {
        markerId: card.its_nid ?? '',
      });
    }

    return {
      currentHoveredCardId,

      onMouseEnterCard,
      onMouseLeaveCard,

      SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE,
    };
  },
};
</script>
