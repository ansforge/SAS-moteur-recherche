<template>
  <div
    class="search-card search-card-cpts"
    :class="[
      `search-card-${mode}`,
    ]"
  >
    <div class="search-card-grid-col">
      <slot name="header" />
    </div>

    <div class="search-card-grid-col">
      <template v-if="mode === 'compact'">
        <div class="search-card-grid-top-right-zone">
          <slot name="phoneNumber" :phoneNumber />
        </div>
      </template>
      <template v-if="mode === 'long'">
        <slot name="phoneNumber" :phoneNumber />
      </template>
    </div>

    <div class="search-card-full-col">
      <slot name="additional" />
      <template v-if="!areWeInCptsCluster">
        <hr class="cpts-separator">
        <button
          class="cpts-link"
          type="button"
          @click="enterCptsCluster(cardData.ss_field_identifiant_finess)"
        >
          Voir tous les professionnels de la CPTS
        </button>
      </template>
    </div>
  </div>
</template>

<script>

import {
  computed,
} from 'vue';

import { useCptsDisplay, useScroll } from '@/composables';

import { phoneHelper } from '@/helpers';

import {
  useCpts,
} from '@/stores';

/**
 * This component is a specialized version of `SearchCardPolymorph` but for CPTS cards
 */
export default {
  name: 'SearchCardPolymorphCpts',
  props: {
    cardData: {
      type: Object,
      default: () => ({}),
    },
    mode: {
      type: String,
      required: true,
    },
  },
  emits: [
    'go-to-cpts-page',
  ],
  setup(props) {
    const cptsStore = useCpts();
    const { setCurrentCpts } = useCptsDisplay();

    const { scrollToTop } = useScroll();

    const areWeInCptsCluster = computed(() => cptsStore.showCptsPage);

    const phoneNumber = computed(() => phoneHelper.formatPhoneNumber(props.cardData?.sm_sas_cpts_care_deal_phones?.[0]));

    function enterCptsCluster(finess) {
      setCurrentCpts(finess);
      scrollToTop();
    }

    return {
      areWeInCptsCluster,
      enterCptsCluster,
      phoneNumber,
    };
  },
};
</script>
