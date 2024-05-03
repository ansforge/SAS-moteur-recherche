<template>
  <div aria-live="polite" class="search-header">
    <h1>
      <span v-if="title" :class="{ 'txt-red': geolocationHasFailed }">
        {{ title }}
      </span>

      <div v-if="!geolocationHasFailed" class="tooltip" data-direction="right">
        <button
          id="sas-search-tooltip-btn"
          class="tooltip__initiator"
          type="button"
          aria-describedby="sas-search-tooltip-msg"
        >
          <i class="sas-icon sas-icon-info-circle" aria-hidden="true" />
        </button>

        <span
          id="sas-search-tooltip-msg"
          class="tooltip__item left"
          role="tooltip"
          aria-hidden="true"
        >
          {{ popinMessage }}
        </span>
      </div>
    </h1>

    <p
      v-if="showLocalTimeZoneLabel"
      class="search-header--subtitle"
    >
      Tous les créneaux sont affichés dans le créneau horaire local
    </p>

    <Switch
      :current="errorCode"
      :cases="[
        {
          id: 'sas_pf_001',
          value: `Le médecin traitant renseigné dans le logiciel de régulation médicale n'a pas pu être identifié`,
        },
        {
          id: 'sas_pf_002',
          value: `Le médecin traitant n'a pas été renseigné dans le logiciel de régulation médicale`,
        },
      ]"
    >
      <template #default="current">
        <p v-if="current.case" class="search-header--subtitle">
          {{ current.case.value }}
        </p>
      </template>
    </Switch>
  </div>
</template>

<script>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import {
  useLrmData,
  useGeolocationData,
 } from '@/stores';
import { routeHelper, formatTimeZoneToHour } from '@/helpers';
import Switch from '@/components/sharedComponents/Switch.component.vue';

/**
 * @typedef {object} Props
 * @property {import('@/types').ICard[]} cards
 */

export default {
  components: {
    Switch,
  },
  props: {
    cards: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(/** @type {Props} */props) {
    const lrmDataStore = useLrmData();
    const geolocationStore = useGeolocationData();
    const {
      hasFailed: geolocationHasFailed,
      geolocation,
      fullAddress,
    } = storeToRefs(geolocationStore);

    const text = lrmDataStore.speciality || routeHelper.getUrlParam('text');

    const title = computed(() => {
      if (geolocationHasFailed.value) {
        return `La zone "${fullAddress.value}" n'est pas reconnue par le moteur de recherche SAS`;
      }

      return geolocation.value?.type === geolocationStore.GEOLOCATION_TYPE.ADDRESS
        ? `Résultats pour l'adresse "${fullAddress.value}"`
        : `Résultats pour la zone "${fullAddress.value}"`;
    });

    const popinMessage = computed(() => (geolocation.value.type === geolocationStore.GEOLOCATION_TYPE.ADDRESS
        ? `L'offre de soins correspondant à ${text} est identifiée en fonction de la distance depuis l'adresse recherchée`
        : `L'offre de soins correspondant à ${text} est identifiée aléatoirement sur la zone correspondante`));

    const showLocalTimeZoneLabel = computed(() => {
      const currentBrowserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

      return props.cards.some((card) => (
        card.calculatedTimeZone
        && formatTimeZoneToHour(card.calculatedTimeZone) !== formatTimeZoneToHour(currentBrowserTimezone)
      ));
    });

    const errorCode = computed(() => (lrmDataStore.preferredDoctorResponseError?.error_code_sas ?? ''));

    return {
      title,
      popinMessage,
      showLocalTimeZoneLabel,
      geolocationHasFailed,
      errorCode,
    };
  },
};
</script>
