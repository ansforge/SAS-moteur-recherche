<template>
  <div>
    <Notification :status="notification.status" :message="notification.message">
      Vous avez adressé un patient {{ dateLabel }} à {{ name }}, {{ recipientType }}
    </Notification>

    <div class="wrapper-btn-actions">
      <button type="submit" class="btn-hightlight-outline form-submit" @click="$emit('notification-confirmed')">
        Fermer
      </button>
    </div>
  </div>
</template>

<script>
import { computed, toRefs } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import Notification from '@/components/sharedComponents/Notification.component.vue';
import OrientationConst from '@/const/orientation.const';

dayjs.locale('fr');

export default {
  name: 'NotificationTab',
  components: { Notification },
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
    notification: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    const timeLabel = computed(() => {
      const labelSeparated = props.calendarSlotData.time
      ? props.calendarSlotData.time.split(' - ') : [];

      return {
        startTime: labelSeparated.length > 0 ? labelSeparated[0] : '',
        endTime: labelSeparated.length > 1 ? labelSeparated[1] : '',
      };
    });

    const dateLabel = computed(() => {
      let dateText = '';

      if (props.type === OrientationConst.SURNUMERAIRE) {
        dateText = dayjs.utc(props.date).format('[le] dddd DD/MM/YY [à] HH[h]mm');
      } else if (props.type === OrientationConst.CRENEAUX) {
        dateText = `${props.calendarSlotData.dateByTimezone.format('[le] dddd DD/MM/YY')} à ${timeLabel.value.startTime}`;
      } else if (props.type === OrientationConst.PLAGE) {
        dateText = `${props.calendarSlotData.dateByTimezone.format('[le] dddd DD/MM/YY')} sur la plage ${timeLabel.value.startTime} - ${timeLabel.value.endTime}`;
      }

      return dateText;
    });

    const { name, infos } = toRefs(props.summary);

    const recipientType = computed(() => (infos.value?.[0]?.label));

    return {
      dateLabel,
      name,
      recipientType,
    };
  },
};
</script>
