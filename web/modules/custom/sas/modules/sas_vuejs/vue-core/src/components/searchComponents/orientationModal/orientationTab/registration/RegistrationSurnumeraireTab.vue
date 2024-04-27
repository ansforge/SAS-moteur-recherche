<template>
  <div class="hours-date">
    <label for="time" class="bold_700"> Date :</label>
    <input
      class="hours-date-input"
      type="date"
      :min="todayDate"
      v-model="selectedDate"
      ref="inputDate"
      @blur="checkDateValidity()"
    >
    <label for="time" class="bold_700"> à :</label>
    <select id="time" v-model="selectedTime" :disabled="(!selectedDate || displayError)">
      <template v-if="selectedDate">
        <option
          v-for="(minute) in hoursDateSurnumeraire"
          :key="minute"
          :value="minute"
        >
          {{ minute }}
        </option>
      </template>
    </select>
  </div>

  <Notification v-if="displayError" status="error">
    Il n'est pas possible de saisir/sélectionner une date/heure dans le passé.
  </Notification>

  <div class="wrapper-btn-actions">
    <button type="button" class="btn-highlight-outline btn-cancel js-btn-cancel" @click="$emit('close')">
      Annuler
    </button>
    <button
      type="submit"
      class="btn-hightlight-outline form-submit"
      @click="save"
      :disabled="(!selectedTime || !selectedDate || displayError)"
    >
      Enregistrer
    </button>
  </div>
</template>

<script>
import { computed, ref } from 'vue';
import dayjs from 'dayjs';
import Notification from '@/components/sharedComponents/Notification.component.vue';
import 'dayjs/locale/fr';

dayjs.locale('fr');

export default {
  name: 'RegistrationSurnumeraireTab',
  emits: ['registrationUpdated', 'close'],
  components: { Notification },
  props: {
    date: {
      type: Object,
      default: () => ({}),
    },
    update: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { emit }) {
    const displayError = ref(false);
    const inputDate = ref(null);
    const todayDate = computed(() => dayjs(new Date()).format('YYYY-MM-DD'));
    const selectedDate = ref('');
    const selectedTime = ref(props.update ? dayjs(props.date).format('HH[h]mm') : null);

    const hoursDateSurnumeraire = computed(() => {
      if (selectedDate.value) {
        let listHours;
        const nextDay = dayjs(todayDate.value).add(1, 'day');

        if (todayDate.value === selectedDate.value) {
          const now = new Date();
          const hoursDiff = nextDay.diff(now, 'minutes', false);
          const hoursDiffMidnight = parseInt(hoursDiff / 15, 10);
          listHours = createListHourSurnumeraire(hoursDiffMidnight, nextDay);
        } else {
          listHours = createListHourSurnumeraire(96, nextDay);
        }
        return listHours;
      }
      return [];
    });

    const createListHourSurnumeraire = (hoursDiffMidnight, nextDay) => {
      const listHours = [];

      for (let i = hoursDiffMidnight; i >= 0; i -= 1) {
        let nextHour;
        if (listHours.length) {
          const timeSplit = listHours[listHours.length - 1].split('h');
          nextHour = dayjs(selectedDate.value).set('hour', timeSplit[0]).set('minute', timeSplit[1]);
        } else {
          nextHour = dayjs(nextDay);
        }

        listHours.push(nextHour
          .subtract(listHours.length ? 15 : 0, 'minutes')
          .format('HH[h]mm'));
      }
      listHours[0] = '23h59';
      return listHours.reverse();
    };

    const save = () => {
      const isValid = inputDate.value.checkValidity();
      checkDateValidity();

      if (isValid && !displayError.value) {
        const dateSplit = selectedDate.value.split('-');
        const timeSplit = selectedTime.value.split('h');
        const year = dateSplit[0];
        const month = parseInt(dateSplit[1], 10) - 1;
        const day = dateSplit[2];
        const hour = timeSplit[0];
        const minute = timeSplit[1];
        const completeDate = dayjs().utc(true)
          .set('year', year)
          .set('month', month)
          .set('date', day)
          .set('hour', hour)
          .set('minute', minute);
        emit('registrationUpdated', completeDate);
      } else {
        displayError.value = true;
      }
    };

    /**
     * check if input date is not in the past
     */
    const checkDateValidity = () => {
      if (!selectedDate.value || dayjs(selectedDate.value).isBefore(todayDate.value)) {
        displayError.value = true;
        selectedTime.value = null;
      } else {
        displayError.value = false;
      }
    };

    return {
      todayDate,
      hoursDateSurnumeraire,
      selectedDate,
      selectedTime,
      save,
      inputDate,
      displayError,
      checkDateValidity,
    };
  },
};
</script>
