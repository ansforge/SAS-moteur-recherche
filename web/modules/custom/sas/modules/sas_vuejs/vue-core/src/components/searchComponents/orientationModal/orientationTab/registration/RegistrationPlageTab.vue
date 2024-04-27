<template>
  <div class="patient-referred-container">
    <div class="patient-slot"> <span class="txt-bold-big">{{ datePlage }} </span>
      <span class="txi-8">- {{ calendarSlotData.time }}</span>
      <span
        v-for="modalite in calendarSlotData.modalite"
        class="tag-letter"
        :key="modalite"
      >
        {{ modalite }}
      </span>
    </div>
    <div class="patient__details">
      <h2 class="patient__details__title">Nombre de patients adressés</h2>
      <div class="patient__details__contenu">
        <div class="patient__details--placeholder">
          <button
            v-if="patientNumber !== patientNumberInit"
            class="patient__details__contenu--sup"
            type="button"
            @click="patientNumber--"
          >
            <span>-</span>
          </button>
        </div>
        <span class="sas__patient__compteur">{{patientNumber}} / {{maxPatientNumber}}</span>
        <div class="patient__details--placeholder">
          <button
            v-if="patientNumber === patientNumberInit"
            class="patient__details__contenu--ajout"
            type="button"
            @click="patientNumber++"
          >
            <span>+</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="wrapper-btn-actions">
    <button
      type="button"
      class="btn-highlight-outline btn-cancel js-btn-cancel"
      @click="$emit('close')"
    >
      Annuler
    </button>
    <button
      type="submit"
      class="btn-hightlight-outline form-submit"
      :disabled="patientNumber === patientNumberInit"
      @click="save"
    >
      Enregistrer
    </button>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useOrientation } from '@/composables';

export default {
  name: 'RegistrationPlageTab',
  emits: ['registrationUpdated', 'close'],
  props: {
    calendarSlotData: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props, { emit }) {
    // Plage
    const { hoursSlotFormatted } = useOrientation();
    const datePlage = ref(props.calendarSlotData.dateByTimezone.format('dddd D/MM/YYYY'));

    const startDatePlage = ref(hoursSlotFormatted(props.calendarSlotData.startHours));
    const endDatePlage = ref(hoursSlotFormatted(props.calendarSlotData.endHours));
    const patientNumber = ref(props.calendarSlotData.orientation_count || 0);
    const patientNumberInit = ref(patientNumber.value);
    const maxPatientNumber = ref(props.calendarSlotData.max_patients);

    const save = () => {
      emit('registrationUpdated');
    };

    return {
      hoursSlotFormatted,
      patientNumber,
      maxPatientNumber,
      patientNumberInit,
      datePlage,
      startDatePlage,
      endDatePlage,
      save,
    };
  },
};
</script>
