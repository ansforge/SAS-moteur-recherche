<template>
  <div class="patient-referred-container">
    <div class="register-tab patient-slot">
      <span class="date-tab txt-bold-big"> {{ dateCreneaux }} </span> <span class="txi-8"> - {{ calendarSlotData.time}}</span>
      <span class="tag-letter" v-for="modalite in calendarSlotData.modalite" :key="modalite"> {{ modalite }}</span>
    </div>
  </div>

  <div class="wrapper-btn-actions">
    <button type="button" class="btn-highlight-outline btn-cancel js-btn-cancel" @click="$emit('close')">
      Annuler
    </button>
    <button type="submit" class="btn-hightlight-outline form-submit" @click="save">
      Enregistrer
    </button>
  </div>
</template>

<script>
import { ref } from 'vue';

export default {
  name: 'RegistrationCreneauxTab',
  emits: ['registrationUpdated', 'close'],
  props: {
    calendarSlotData: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props, { emit }) {
    const dateCreneaux = ref(props.calendarSlotData.dateByTimezone.format('dddd D/MM/YYYY'));
    const save = () => {
      emit('registrationUpdated');
    };
    return {
      dateCreneaux,
      save,
    };
  },
};
</script>
