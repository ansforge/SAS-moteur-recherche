<template>
  <div>
    <div class="confirm-tab">
      <div class="temp-reg-tab">
        <div v-if="type === OrientationConst.SURNUMERAIRE"> Vous adressez un patient surnuméraire à</div>
        <div v-if="type === OrientationConst.PLAGE || type === OrientationConst.CRENEAUX"> Vous adressez un patient à</div>
      </div>
      <HealthOfferSummary
        class="modal-summary-grid modal-confirm-summary-grid"
        v-bind="summary"
      />
      <div class="date-popin"> {{ dateLabel }}</div>
    </div>
    <div class="wrapper-btn-actions">
      <button type="button" class="btn-highlight-outline btn-cancel js-btn-cancel" @click="update">
        Modifier
      </button>
      <button type="submit" class="btn-hightlight-outline form-submit" @click="save">
        Confirmer
      </button>
    </div>

  </div>
</template>

<script>

import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import utc from 'dayjs/plugin/utc';
import { computed } from 'vue';
import OrientationConst from '@/const/orientation.const';

import HealthOfferSummary from '@/components/sharedComponents/HealthOfferSummary.component.vue';

dayjs.locale('fr');
dayjs.extend(utc);

export default {
  name: 'ConfirmationTab',
  components: {
    HealthOfferSummary,
  },
  props: {
    summary: {
      type: Object,
      default: () => ({}),
    },
    date: {
      type: [Object, String],
      default: () => ({}),
    },
    type: {
      type: String,
      default: '',
    },
    calendarSlotData: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props, { emit }) {
    const timeLabel = computed(() => {
      const labelSeparated = props.calendarSlotData.time
      ? props.calendarSlotData.time.split(' - ') : [];

      return {
        startTime: labelSeparated.length > 0 ? labelSeparated[0] : '',
        endTime: labelSeparated.length > 1 ? labelSeparated[1] : '',
      };
    });

    const dateLabel = computed(() => {
      switch (props.type) {
        case OrientationConst.SURNUMERAIRE:
          return dayjs.utc(props.date).format('[le] dddd DD/MM/YY [à] HH[h]mm');

        case OrientationConst.CRENEAUX:
          return `${props.calendarSlotData.dateByTimezone.format('[le] dddd DD/MM/YY [à] ')} ${timeLabel.value.startTime}`;

        case OrientationConst.PLAGE:
          return `${props.calendarSlotData.dateByTimezone.format('[le] dddd DD/MM/YY')} sur la plage ${timeLabel.value.startTime} - ${timeLabel.value.endTime}`;

        default:
          return '';
      }
    });

    const update = () => {
      emit('confirmationUpdate', props.date);
    };

    const save = () => {
      emit('confirmationSave', props.date);
    };

    return {
      timeLabel,
      dateLabel,
      save,
      update,
      OrientationConst,
    };
  },
};
</script>
