<template>
  <div class="accordion box">
    <button
      class="btn-collapse btn-accordion"
      type="button"
      :aria-expanded="open"
      aria-controls="collapseDoctors"
      data-once="collapse"
      @click="open = !open"
    >
      Les horaires
    </button>

    <div
      id="collapseDoctors"
      class="collapse"
      :class="[{ collapsed: !open }, { collapsing: isCollapsing }]"
      data-height="304"
    >
      <div class="collapse-container">
        <template v-if="!loading">
          <div v-if="isTodayExists" class="tag-with-chip">
            <span class="chip chip-blue" />
            Aujourd'hui
          </div>

          <div class="tag-with-chip">
            <span class="chip" />
            {{ popinSnpSettingsData.group1.nom_snp }}
          </div>

          <div>
            <ul class="resetul list-flex">
              <li>
                <span class="tag-letter">{{ popinSnpSettingsData.group4.initial_cabinet }}</span>
                {{ popinSnpSettingsData.group4.cabinet }}
              </li>
              <li>
                <span class="tag-letter">{{ popinSnpSettingsData.group4.initial_teleconsultation }}</span>
                {{ popinSnpSettingsData.group4.teleconsultation }}
              </li>
              <li>
                <span class="tag-letter">{{ popinSnpSettingsData.group4.initial_domicile }}</span>
                {{ popinSnpSettingsData.group4.domicile }}
              </li>
            </ul>
          </div>

          <div
            v-if="showLocalTimeZoneLabel"
            class="info-local-hour"
            title="Tous les créneaux sont affichés dans le fuseau horaire local"
            tabindex="0"
          >
            <i class="icon-information-circle-solid mr-5" aria-hidden="true" />
            <span>Tous les créneaux sont affichés dans le fuseau horaire local</span>
          </div>
        </template>

        <p class="mention">Voici les créneaux disponibles sous 48H</p>

        <div class="wrapper-pagination">
          <Pagination
            :start="start"
            :end="end"
            withDay
            :controls="false"
          />
        </div>

        <NavigableCalendar
          :slots="slots"
          :loading="loading"
          :startDate="start"
        />
      </div>
    </div>
  </div>
</template>

<script>
import {
 provide, inject, ref, computed,
} from 'vue';

import dayjs from 'dayjs';
import 'dayjs/locale/fr';

import NavigableCalendar from '@/components/calendars/NavigableCalendar.component.vue';
import Pagination from '@/components/calendars/Pagination.component.vue';

import { SlotsModel } from '@/models';
import { CalendarService } from '@/services';
import { formatTimeZoneToHour } from '@/helpers';

export default {
  components: { Pagination, NavigableCalendar },
  setup() {
    const placeNid = inject('placeNid');
    const open = ref(true);
    const isCollapsing = ref(false);
    const start = ref(dayjs().format('YYYY-MM-DD'));
    const end = ref(dayjs(start.value).add(2, 'day').format('YYYY-MM-DD'));
    const slots = ref(new SlotsModel());
    const popinSnpSettingsData = ref({});
    const loading = ref(true);

    const isTodayExists = computed(() => slots.value.isTodayExists());
    const isAfterTodayExists = computed(() => slots.value.isTodayExists());

    const timezone = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';

    const showLocalTimeZoneLabel = computed(() => {
      const currentBrowserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return formatTimeZoneToHour(timezone) !== formatTimeZoneToHour(currentBrowserTimezone);
    });

    async function initConfiguration() {
      loading.value = true;
      popinSnpSettingsData.value = await CalendarService.getPopinCreateDispoConfig();

      fetchSlots();
    }

    initConfiguration();

    async function fetchSlots() {
      try {
        const res = new SlotsModel(Object.values(CalendarService.getAgregSlots(placeNid)), timezone, true);
        slots.value = res;

        loading.value = false;
      } catch (e) {
        loading.value = false;
        console.error(e);
      }
    }

    provide('popinSnpSettingsData', popinSnpSettingsData);

    return {
      loading,
      isTodayExists,
      open,
      isCollapsing,
      start,
      end,
      slots,
      popinSnpSettingsData,
      isAfterTodayExists,
      showLocalTimeZoneLabel,
      formatTimeZoneToHour,
    };
  },
};
</script>
