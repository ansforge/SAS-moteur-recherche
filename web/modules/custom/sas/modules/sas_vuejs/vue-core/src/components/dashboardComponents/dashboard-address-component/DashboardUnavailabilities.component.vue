<template>
  <div class="db-unavailability">
    <div class="db-address-title">
      <h3><i class="sas-icon sas-icon-sun" aria-hidden="true" /> Périodes d'indisponibilité</h3>
      <PopinIndispo
        popinUnavailabilityTitle="Éditer"
        :timeslotNid="timeslotNid"
        :isDisabled="isBtnDisabled"
        source="dashboard"
        @update-unavailibities-info="getUnavaibility"
      />
    </div>

    <RingLoader v-if="showLoader" />

    <ul v-else-if="isUnavailable" class="unavailable-periods">
      <li
        v-for="(date, index) in unavailableDates.dates"
        :key="index"
        :class="{ 'active-period': dateRange(date.from, date.to) }"
      >
        du {{ dateFormated(date.from) }}
        au {{ dateFormated(date.to) }}

        <span v-if="dateRange(date.from, date.to)">{{activePeriodTitle}}</span>
      </li>
    </ul>

    <div v-else-if="unavailableDates.vacationMode" class="active-period">
      <span>{{activePeriodTitle}}</span>
    </div>

    <div v-else class="db-empty">
      <p>Éditer pour déclarer des périodes d'indisponibilité</p>
    </div>
  </div>
</template>

<script>

import { computed, ref } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isBetween from 'dayjs/plugin/isBetween';
import PopinIndispo from '@/components/calendars/PopinIndispo.component.vue';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';

dayjs.extend(isBetween);

export default {
  components: {
    PopinIndispo,
    RingLoader,
  },
  props: {
    timeslotNid: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    const unavailableDates = ref({});
    const activePeriodTitle = "Période d'indisponibilité actuellement active pour ce lieu, vos disponibilités ne sont plus visibles dans le SAS.";

    // loader feature
    const showLoader = ref(!!props.timeslotNid);

    function getUnavaibility(val) {
      showLoader.value = true;
      unavailableDates.value = val;
      showLoader.value = false;
    }

    const isUnavailable = computed(() => unavailableDates.value.dates && unavailableDates.value.dates.length && !unavailableDates.value.vacationMode);

    function dateRange(start, end) {
      return dayjs().isBetween(dayjs(start), dayjs(end));
    }

    function dateFormated(date) {
      return dayjs(date).format('DD/MM/YY');
    }

    const isBtnDisabled = computed(() => !props.timeslotNid?.length);

    return {
      getUnavaibility,
      unavailableDates,
      isUnavailable,
      dateRange,
      dateFormated,
      activePeriodTitle,
      showLoader,
      isBtnDisabled,
    };
  },
};
</script>
