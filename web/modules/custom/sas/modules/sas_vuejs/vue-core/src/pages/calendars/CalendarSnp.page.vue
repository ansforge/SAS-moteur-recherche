<template>
  <template v-if="!loading">
    <div v-if="slots.isTodayExists()" class="tag-with-chip">
      <span class="chip chip-blue" />
      Aujourd'hui
    </div>

    <div v-if="slots.isAfterTodayExists()" class="tag-with-chip">
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

    <div v-if="showLocalTimeZoneLabel" class="info-local-hour" title="Tous les créneaux sont affichés dans le fuseau horaire local" tabindex="0">
      <i class="icon-information-circle-solid mr-5" aria-hidden="true" />
      <span>Tous les créneaux sont affichés dans le fuseau horaire local</span>
    </div>
  </template>

  <div class="wrapper-pagination">
    <Pagination
      :start="start"
      :end="end"
      withDay
      :hidePrevious="hidePrevious()"
      @next="triggerNextThreeDays"
      @previous="triggerPreviousThreeDays"
      :controls="source !== 'dashboard'"
    />
  </div>

  <NavigableCalendar
    currentPage="deepPage"
    :slots="slots"
    :loading="loading"
    :startDate="start"
    @open-modal-orientation="setOrientationData"
  />

  <OrientationModal
    v-if="openOrientationModal"
    v-bind="orientationData"
    :open="openOrientationModal"
    @close="openOrientationModal = false"
    @refresh-slots="refreshSlot"
  />
</template>

<script>
import {
 provide, inject, ref, nextTick, computed, onMounted, onUnmounted,
} from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import CalendarService from '@/services/calendar.service';
import { SlotsModel } from '@/models';
import { useUser } from '@/composables';
import Pagination from '@/components/calendars/Pagination.component.vue';
import NavigableCalendar from '@/components/calendars/NavigableCalendar.component.vue';
import OrientationModal from '@/components/searchComponents/orientationModal/OrientationModal.component.vue';
import { formatTimeZoneToHour, handleDateWithOffset } from '@/helpers';

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
  components: { Pagination, NavigableCalendar, OrientationModal },
  props: {
    source: {
      type: String,
      default: '',
    },
  },
  setup() {
    const scheduleId = inject('scheduleId');
    const { currentUser, getCurrentUser } = useUser();
    const timeSLotTimezone = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';
    const systemTz = dayjs.tz.guess();
    const start = ref(getPayloadDate(dayjs(), systemTz, true));
    const end = ref(getPayloadDate(dayjs(start.value).add(2, 'day').format('YYYY-MM-DD'), systemTz));
    const today = dayjs().format('YYYY-MM-DD');
    const slots = ref(new SlotsModel());
    const loading = ref(false);
    const popinSnpSettingsData = ref({});
    const currentPsData = ref({});
    const orientationData = ref({});
    const openOrientationModal = ref(false);
    const currentSlot = ref({});

    const showLocalTimeZoneLabel = computed(() => {
      const currentBrowserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return formatTimeZoneToHour(timeSLotTimezone) !== formatTimeZoneToHour(currentBrowserTimezone);
    });

    initConfiguration();

    onMounted(async () => {
      const listItems = document.querySelectorAll('.tab-item');

      if (listItems.length) {
        listItems.forEach((li) => {
          const currentBtn = li.querySelector('button');
          currentBtn.addEventListener('click', getCurrentPsData);
        });
      }

      await getCurrentUser();
      getCurrentPsData();
    });

    onUnmounted(() => {
      const listItems = document.querySelectorAll('.tab-item');

      if (listItems.length) {
        listItems.forEach((li) => {
          const currentBtn = li.querySelector('button');
          currentBtn.removeEventListener('click', getCurrentPsData);
        });
      }
    });

    function getPayloadDate(date, systemTimezone, isStartDate = false) {
      const timezoneVal = systemTimezone === 'Europe/Paris' ? '+01:00' : formatTimeZoneToHour(systemTimezone);
      if (isStartDate) {
        let startDate = date.utc();
        startDate = startDate.tz(systemTimezone);
        return `${startDate.format('YYYY-MM-DDTHH:mm:ss')}${timezoneVal}`;
      }
      return `${date}T23:59:59${timezoneVal}`;
    }

    async function initConfiguration() {
      loading.value = true;

      popinSnpSettingsData.value = await CalendarService.getPopinCreateDispoConfig();

      fetchSlots(start.value, end.value);
    }

    async function fetchSlots(startDate, endDate) {
      loading.value = true;
      const paramsConfig = {
        scheduleId,
        startDate,
        endDate,
        orientationStrategy: 1,
        showExpired: 0,
        context: 'deep-page',
      };
      try {
        const res = new SlotsModel(await CalendarService.getSlotsByScheduleId(paramsConfig), timeSLotTimezone);
        slots.value = res;
      } catch (e) {
        console.error(e);
      } finally {
        loading.value = false;
      }
    }

    function triggerPreviousThreeDays() {
      const timezoneVal = systemTz === 'Europe/Paris' ? '+01:00' : formatTimeZoneToHour(systemTz);

      // send with utc and hours format if start is today
      if (dayjs(start.value).subtract(3, 'day').format('YYYY-MM-DD') === today) {
        start.value = `${dayjs().format('YYYY-MM-DDTHH:mm:ss')}${timezoneVal}`;
      } else {
        start.value = `${dayjs(start.value).subtract(3, 'day').format('YYYY-MM-DD')}T00:00:00${timezoneVal}`;
      }

      end.value = `${handleDateWithOffset(timeSLotTimezone, end.value, 'back', 3, 'YYYY-MM-DD')}T23:59:59${timezoneVal}`;
      slots.value = new SlotsModel();
      nextTick(() => {
        fetchSlots(start.value, end.value);
      });
    }

    function triggerNextThreeDays() {
      const timezoneVal = systemTz === 'Europe/Paris' ? '+01:00' : formatTimeZoneToHour(systemTz);

      start.value = `${dayjs(start.value).add(3, 'day').format('YYYY-MM-DD')}T00:00:00${timezoneVal}`;
      end.value = `${handleDateWithOffset(timeSLotTimezone, end.value, 'forward', 3, 'YYYY-MM-DD')}T23:59:59${timezoneVal}`;
      slots.value = new SlotsModel();
      nextTick(() => {
        fetchSlots(start.value, end.value);
      });
    }

    function hidePrevious() {
        return dayjs(start.value).format('YYYY-MM-DD') === today;
    }

    function getCurrentPsData() {
      setTimeout(() => {
        const currentTab = document.querySelector('.same-address-mounted:not(.closedtab)');

        if (currentTab) {
          const currentNid = currentTab?.dataset?.placeid || null;
          currentPsData.value = window?.API[currentNid]?.recipient || {};
        } else {
          currentPsData.value = window?.API?.recipient || {};
        }
      }, 500);
    }

    function setCardData() {
      return {
        ss_sas_cpts_finess: null,
        ss_field_identifiant_rpps: currentPsData?.value?.effector_rpps || null,
        ss_field_personne_adeli_num: currentPsData?.value?.effector_adeli || null,
        ss_field_identifiant_finess: currentPsData?.value?.structure_finess || '',
        ss_field_identifiant_str_finess: currentPsData?.value?.structure_finess || '',
        tm_X3b_und_title: currentPsData?.value?.name ? [currentPsData.value.name] : [],
        tm_X3b_und_field_profession_name: currentPsData?.value?.structure_type ? [currentPsData.value.structure_type] : [],
        ss_field_address: currentPsData?.value?.address || '',
        ss_field_identif_siret: currentPsData?.value?.structure_siret || null,
        tm_X3b_und_field_department: currentPsData?.value?.county ? [currentPsData.value.county] : [],
        ss_field_department_code: currentPsData?.value?.county_number || '',
        tm_X3b_und_establishment_type_names: currentPsData?.value?.structure_type ? [currentPsData.value.structure_type] : [],
        effector_is_sas: currentPsData?.value?.effector_is_sas ?? false,
        final_phone_number: currentPsData?.value?.phone_number?.length ? currentPsData.value.phone_number[0] : '',
      };
    }

    function setOrientationData(evtData) {
      currentSlot.value = evtData.calendarSlotData || {};

      orientationData.value = {
        cardData: setCardData(),
        calendarSlotData: evtData.calendarSlotData || {},
        type: evtData.type || '',
      };

      openOrientationModal.value = true;
    }

    function refreshSlot(slotData) {
      const isErrorStatus = !slotData.data.slot || slotData.status === 'error';

      if (!isErrorStatus) {
        const newSlot = slotData.data.slot;
        const slotIdx = slots.value.slots
          ? slots.value.slots.findIndex((slot) => slot.id === newSlot.id) : -1;

        if (
          slots.value.slots
          && slots.value.slots[slotIdx]
        ) {
          if (newSlot.orientation_count) {
            slots.value.slots[slotIdx].orientation_count = newSlot.orientation_count || -1;
          }

          const isSlotFull = (
            slots.value.slots[slotIdx].max_patients === -1
            || slots.value.slots[slotIdx].max_patients === slots.value.slots[slotIdx].orientation_count
          );

          if (isSlotFull) {
            slots.value.slots.splice(slotIdx, 1);
          }
        }
      } else {
        const slotIdx = slots.value.slots
          ? slots.value.slots.findIndex((slot) => slot.id === currentSlot.value.id) : -1;
        if (slotIdx !== -1) {
          slots.value.slots.splice(slotIdx, 1);
        }
      }
      currentSlot.value = {};
    }

    provide('popinSnpSettingsData', popinSnpSettingsData);
    provide('currentUser', currentUser);
    provide('isParticipationChecked', false);

    return {
      popinSnpSettingsData,
      today,
      scheduleId,
      start,
      end,
      slots,
      loading,
      currentPsData,
      showLocalTimeZoneLabel,
      currentUser,
      orientationData,
      openOrientationModal,
      triggerNextThreeDays,
      triggerPreviousThreeDays,
      formatTimeZoneToHour,
      setOrientationData,
      getCurrentUser,
      refreshSlot,
      hidePrevious,
    };
  },
};
</script>
