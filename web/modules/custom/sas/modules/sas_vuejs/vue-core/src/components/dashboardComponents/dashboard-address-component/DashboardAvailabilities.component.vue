<template>
  <div class="db-availability">
    <div class="db-address-title">
      <h3><i class="sas-icon sas-icon-calendar" aria-hidden="true" /> Disponibilités</h3>

      <div v-if="showEditorPhone" class="phone" :class="{ 'txt-red': !hasNumber }">
        <strong>Téléphone :</strong> {{ currentPhoneNumber }}
      </div>

      <!-- calendar page link -->
      <button
        v-if="!calendarUrl?.length && showBtn"
        type="button"
        class="btn-highlight"
        disabled
      >
        Éditer
      </button>

      <a
        v-else-if="showBtn"
        class="btn-highlight"
        :href="$sanitize(calendarUrl)"
        target="_blank"
        rel="noopener"
      >Éditer</a>
    </div>

    <!-- display text for SAS with SOS Médecin -->
    <p v-if="isSosMedecinsChecked" class="sas-sos-medecins">En tant que participant au SAS via SOS&nbsp;Médecins, vos créneaux de disponibilités sont édités par le gestionnaire de structure de l'association SOS&nbsp;Médecins déclarée dans votre formulaire</p>

    <!-- display of schedule -->
    <div v-else-if="noSnpCalendarMsg" class="db-empty">
      <p>Éditer pour déclarer des disponibilités</p>
    </div>

    <template v-else-if="!isSosMedecinsChecked && !isLoading">
      <div v-if="!isEditorsChecked">
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

      <div class="wrapper-pagination">
        <Pagination
          :start="start"
          :end="end"
          :controls="false"
        />
      </div>

      <NavigableCalendar
        :slots="currentSlots"
        :loading="isLoading"
        :startDate="start"
      />
    </template>
  </div>
</template>

<script>
import { computed } from 'vue';
import dayjs from 'dayjs';
import Pagination from '@/components/calendars/Pagination.component.vue';
import NavigableCalendar from '@/components/calendars/NavigableCalendar.component.vue';
import { useUserDashboard } from '@/stores';
import { SlotsModel } from '@/models';

export default {
  components: {
    Pagination,
    NavigableCalendar,
  },
  props: {
    addressData: {
      type: Object,
      default: () => ({}),
    },
    start: {
      type: String,
      default: '',
    },
    end: {
      type: String,
      default: '',
    },
    popinSnpSettingsData: {
      type: Object,
      default: () => ({}),
    },
    currentCalendar: {
      type: Array,
      default: () => ([]),
    },
    aggregPhone: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    const userDashboardStore = useUserDashboard();

    const calendarUrl = computed(() => props.addressData.calendar_url || '');

    const showBtn = computed(() => !isEditorsChecked.value && !isSosMedecinsChecked.value);

    const allSlots = computed(() => userDashboardStore.allSlots);
    const currentSlots = computed(() => {
      const timezone = dayjs.tz.guess();

      if (!isEditorsChecked.value) {
        const slots = allSlots.value[props.addressData.schedule_id] || [];
        return new SlotsModel(slots, timezone);
      }

      const aggregSlots = [];
      props.currentCalendar.forEach((day) => {
        const slots = day.slots ?? [];
        slots.forEach((slot) => {
          aggregSlots.push(slot);
        });
      });

      return new SlotsModel(aggregSlots, timezone, true);
    });

    const isLoading = computed(() => userDashboardStore.scheduleIsLoading);
    const isSosMedecinsChecked = computed(() => userDashboardStore.isSosMedecinsChecked);
    const isEditorsChecked = computed(() => userDashboardStore.isEditorsChecked);

    const showEditorPhone = computed(() => isEditorsChecked.value && props.addressData.calendars?.length > 1);
    const hasNumber = computed(() => !!props.aggregPhone);
    const currentPhoneNumber = computed(() => (hasNumber.value ? props.aggregPhone : 'N° de téléphone non renseigné'));

    const noSnpCalendarMsg = computed(() => (
      !isEditorsChecked.value
      && showBtn.value
      && !currentSlots.value.slots.length
      && !isLoading.value
    ));

    return {
      calendarUrl,
      currentSlots,
      isLoading,
      showBtn,
      isSosMedecinsChecked,
      isEditorsChecked,
      hasNumber,
      currentPhoneNumber,
      showEditorPhone,
      noSnpCalendarMsg,
    };
  },
};
</script>
