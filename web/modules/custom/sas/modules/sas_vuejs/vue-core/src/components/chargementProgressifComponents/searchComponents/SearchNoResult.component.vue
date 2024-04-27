<template>
  <div class="sas-no-result">
    <p>{{ noResultText }}</p>
    <button type="button" class="btn-highlight-outline" @click="handleClick">
      {{ buttonText }}
    </button>
  </div>
</template>

<script>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import {
  useUserData,
  useSearchData,
  useGeolocationData,
} from '@/stores';

import {
  SAS_NO_HEALTH_OFFER_IN_ZONE_SENTENCE,
  SAS_NO_OVERBOOKING_HEALTH_OFFER_IN_ZONE_SENTENCE,
  SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE,
  SAS_SEARCH_OVERBOOKING_FILTER_LABEL,
  SAS_GEOLOCATION_HAS_FAILED,
} from '@/const';

import {
  routeHelper,
} from '@/helpers';

export default {
  emits: ['change-to-overbooking-filter'],
  setup(props, { emit }) {
    const userStore = useUserData();
    const currentUser = computed(() => userStore.currentUser);

    const searchDataStore = useSearchData();
    const {
      isFiltered,
    } = storeToRefs(searchDataStore);

    const geolocationStore = useGeolocationData();

    const noResultText = computed(() => {
      if (geolocationStore.hasFailed) {
        const location = routeHelper.getUrlParam('loc');
        return SAS_GEOLOCATION_HAS_FAILED(location);
      }

      if (!currentUser.value.isRegulateurOSNP) {
        return SAS_NO_HEALTH_OFFER_IN_ZONE_SENTENCE;
      }

      if (isFiltered.value) {
        return `${SAS_NO_HEALTH_OFFER_IN_ZONE_SENTENCE} ${SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE}`;
      }

      if (!isFiltered.value) {
        return SAS_NO_OVERBOOKING_HEALTH_OFFER_IN_ZONE_SENTENCE;
      }

      return 'Une erreur est survenue.';
    });

    const buttonText = computed(() => (
      (currentUser.value.isRegulateurOSNP && isFiltered.value)
      ? `Rechercher ${SAS_SEARCH_OVERBOOKING_FILTER_LABEL}`
      : 'Retour à l\'accueil'
    ));

    function handleClick() {
      if (currentUser.value.isRegulateurOSNP && isFiltered.value) {
        emit('change-to-overbooking-filter');
      } else {
        window.location.href = '/';
      }
    }

    return {
      noResultText,
      buttonText,
      handleClick,
    };
  },
};
</script>
