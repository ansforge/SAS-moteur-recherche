<template>
  <div>
    <div class="calendar-header">
      <h1 class="page-title">Ajouter ou modifier vos disponibilités</h1>
      <div class="calendar-header-content d-flex justify-content-between">
        <div class="calendar-header-address">
          <p v-if="address.length"><strong>Adresse : </strong>{{ address }}</p>
        </div>
        <div class="wrapper-buttons">
          <PopinCreateDispo :config="popinCreateDispoConfig" @submited="scrollToCalendar(), fetchSlots(start, end)">
            <template v-slot="{ openModal }">
              <button class="btn-highlight with-icon js-btn-open-modal-sas" type="button" @click="openModal">
                Créer des disponibilités
              </button>
            </template>
          </PopinCreateDispo>
          <PopinIndispo @submit="getUnavaibility" />
          <PopinInfo />
        </div>
      </div>
    </div>
    <div class="calendar-legend">
      <div class="tag-with-chip">
        <span class="chip" />
        Disponibilités
      </div>
      <div class="tag-with-chip">
        <span class="chip chip-orange" />
        Réservés
      </div>
      <p v-if="ifCentreDeSante" class="calendar-cds-notice">Si vous renseignez directement sur l'agenda de la
        plateforme numérique 2 heures par semaine pour chaque médecin généraliste
        de votre centre sans avoir coché la section "J'accepte d'être contacté par la régulation afin d'être sollicité
        pour prendre
        des patients en sus de mes disponibilités", alors vous n'êtes pas éligible à la rémunération SAS prévue par
        l'avenant n°4</p>
    </div>

    <div v-if="showLocalTimeZoneLabel" class="info-local-hour" title="Tous les créneaux sont affichés dans le fuseau horaire local" tabindex="0">
      <i class="icon-information-circle-solid mr-5" aria-hidden="true" />
      <span>Tous les créneaux sont affichés dans le fuseau horaire local</span>
    </div>

    <div class="calendar-table" ref="calendarDomRef">
      <Pagination
        :start="start"
        :end="end"
        :hidePrevious="isCurrentWeek"
        @next="triggerNextWeek"
        @previous="triggerPreviousWeek"
      />

      <CalendarFull
        :slots="slots"
        :start="start"
        :end="end"
        :popin-create-dispo-config="popinCreateDispoConfig"
        :popin-delete-config="popinDeleteConfig"
        :unavailable-dates="unavailableDates"
      />
    </div>
  </div>
</template>

<script>

import {
  ref, computed, nextTick, provide,
} from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isBetween from 'dayjs/plugin/isBetween';
import CalendarService from '@/services/calendar.service';
import PopinCreateDispo from '@/components/calendars/PopinCreateDispo.component.vue';
import PopinInfo from '@/components/calendars/PopinInfo.component.vue';
import PopinIndispo from '@/components/calendars/PopinIndispo.component.vue';
import CalendarFull from '@/components/calendars/CalendarFull.component.vue';
import Pagination from '@/components/calendars/Pagination.component.vue';
import { SlotsModel, SnpPopinConfigModel, SnpIndispoModel } from '@/models';
import { formatTimeZoneToHour, handleDateWithOffset } from '@/helpers';

dayjs.extend(isBetween);

export default {
  components: {
    CalendarFull, Pagination, PopinCreateDispo, PopinInfo, PopinIndispo,
  },
  setup() {
    const popinCreateDispoConfig = ref(new SnpPopinConfigModel());
    const popinDeleteConfig = ref({ title: '', subtitle: '' });
    const calendarDomRef = ref(null);
    const ifCentreDeSante = CalendarService.isCentreDeSante();
    const unavailableDates = ref({});
    const playScrollToSpecificHour = ref(true);
    const timezone = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';

    const showLocalTimeZoneLabel = computed(() => {
      const currentBrowserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return formatTimeZoneToHour(timezone) !== formatTimeZoneToHour(currentBrowserTimezone);
    });

    async function fetchPopinCreateDispoConfig() {
      popinCreateDispoConfig.value = new SnpPopinConfigModel(await CalendarService.getPopinCreateDispoConfig());
    }

    fetchPopinCreateDispoConfig();

    async function fetchPopinDeleteConfig() {
      popinDeleteConfig.value = await CalendarService.getPopinDeleteConfig();
    }

    fetchPopinDeleteConfig();

    async function getUnavaibility() {
      unavailableDates.value = new SnpIndispoModel(await CalendarService.getIndispoByNodeId(CalendarService.getNodeId()));
    }

    getUnavaibility();

    const indexOfCurrentDateInWeek = dayjs().day();
    const start = ref(getPayloadDate(dayjs().subtract(indexOfCurrentDateInWeek - 1, 'day').format('YYYY-MM-DD'), timezone, true));
    const end = ref(getPayloadDate(dayjs(start.value).add(6, 'day').format('YYYY-MM-DD'), timezone));
    const slots = ref(new SlotsModel());
    const isCurrentWeek = computed(() => dayjs().isBetween(start.value, end.value));

    async function fetchSlots(startDate, endDate) {
      const paramsConfig = {
        scheduleId: CalendarService.getScheduleId(),
        startDate,
        endDate,
        orientationStrategy: 3,
        showExpired: 1,
        context: 'calendar',
      };
      const res = new SlotsModel(await CalendarService.getSlotsByScheduleId(paramsConfig), timezone);
      slots.value = res;
      const firstSlotHour = res.getFirstSlotHour();
      if (playScrollToSpecificHour.value && slots.value.slots?.length > 0) {
        scrollToSpecificHour(firstSlotHour >= 8 ? 8 : firstSlotHour);
      }
    }
    provide('fetchSlots', fetchSlots);

    fetchSlots(start.value, end.value);

    function getPayloadDate(date, currentTimezone, isStartDate = false) {
      const timezoneVal = currentTimezone === 'Europe/Paris' ? '+01:00' : formatTimeZoneToHour(currentTimezone);
      return `${date}T${isStartDate ? '00:00:00' : '23:59:59'}${timezoneVal}`;
    }

    function triggerNextWeek() {
      start.value = getPayloadDate(handleDateWithOffset(timezone, start.value, 'forward', 7, 'YYYY-MM-DD', true), timezone, true);
      end.value = getPayloadDate(handleDateWithOffset(timezone, end.value, 'forward', 7, 'YYYY-MM-DD'), timezone);
      slots.value = new SlotsModel();
      playScrollToSpecificHour.value = false;
      playScrollToSpecificHour.value = false;

      nextTick(() => {
        fetchSlots(start.value, end.value);
      });
    }

    function triggerPreviousWeek() {
      start.value = getPayloadDate(handleDateWithOffset(timezone, start.value, 'previous', 7, 'YYYY-MM-DD', true), timezone, true);
      end.value = getPayloadDate(handleDateWithOffset(timezone, end.value, 'previous', 7, 'YYYY-MM-DD'), timezone);
      slots.value = new SlotsModel();
      playScrollToSpecificHour.value = false;
      nextTick(() => {
        fetchSlots(start.value, end.value);
      });
    }

    function scrollToCalendar() {
      const top = calendarDomRef.value.offsetTop || 0;
      window.scrollTo({
        top,
        behavior: 'smooth',
      });
    }

    function scrollToSpecificHour(firstSlotHour) {
      const scrollToElId = `${(`0${ firstSlotHour }`).slice(-2) }:00`;
      // The 309 is the height of the fixed title of the table
      const targetPoint = document.getElementById(scrollToElId).getBoundingClientRect().top + window.scrollY - 309;

      window.scrollTo({
        top: targetPoint,
        behavior: 'smooth',
      });
    }

    // fetch address feature
    const address = computed(() => window?.API?.['time-slot-schedule']?.full_address || '');

    return {
      calendarDomRef,
      popinCreateDispoConfig,
      start,
      end,
      slots,
      fetchSlots,
      scrollToCalendar,
      isCurrentWeek,
      triggerPreviousWeek,
      triggerNextWeek,
      getUnavaibility,
      ifCentreDeSante,
      unavailableDates,
      showLocalTimeZoneLabel,
      formatTimeZoneToHour,
      popinDeleteConfig,
      address,
    };
  },
};
</script>
