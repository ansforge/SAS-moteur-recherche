<template>
  <div class="wrapper-calendar-pagination">
    <template v-if="controls">
      <button v-if="!hidePrevious" type="button" class="btn-highlight only-icon btn-pager" @click="$emit('previous')">
        <i class="icon icon-left" aria-hidden="true" />
        <span class="sr-only">Précédent</span>
      </button>
      <button type="button" class="btn-highlight only-icon btn-pager" @click="$emit('next')">
        <i class="icon icon-right" aria-hidden="true" />
        <span class="sr-only">Suivant</span>
      </button>
    </template>
    <p class="dates">
      {{startDate}} - {{endDate}}
    </p>
  </div>
</template>

<script>
import { computed } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';

dayjs.locale('fr');

export default {
  props: {
    start: {
      type: String,
      required: true,
    },
    end: {
      type: String,
      default: '',
    },
    hidePrevious: {
      type: Boolean,
      default: false,
    },
    controls: {
      type: Boolean,
      default: true,
    },
    withDay: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const timezone = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';

    const startDate = computed(() => (props.withDay
      ? dayjs(props.start).format('dddd D MMMM YYYY')
      : dayjs(props.start).format('D MMMM YYYY')));

    const endDate = computed(() => {
      // check offset value to handle daylight saving time
      if (
        timezone === 'Europe/Paris'
        && dayjs(props.end).utcOffset() === 120
        ) {
          return props.withDay ? dayjs(props.end).utc().format('dddd D MMMM YYYY') : dayjs(props.end).utc().format('D MMMM YYYY');
      }
      return props.withDay ? dayjs(props.end).format('dddd D MMMM YYYY') : dayjs(props.end).format('D MMMM YYYY');
    });

    return {
      startDate,
      endDate,
    };
  },
};
</script>
