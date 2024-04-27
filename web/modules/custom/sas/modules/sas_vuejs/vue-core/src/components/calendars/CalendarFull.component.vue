<template>
  <div class="wrapper-calendar">
    <table>
      <caption class="sr-only">Calendrier des disponibilités du professionnel de santé</caption>
      <thead>
        <tr>
          <th scope="col" v-for="(day, i) in days" :key="day">
            {{ baseDays[i] }} {{ day.dayNumber }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(_, hour) in 24" :key="hour" :id="getTime(hour)">
          <td
            v-for="(day, dayIndex) in days"
            :key="day.dayNumber"
            :data-date="day.dayNumber"
            :data-hour="hour"
            :class="{ unavailable: getUnavailableStyle(day.fullDate) }"
          >
            <span v-if="dayIndex === 0" class="time">{{ getTime(hour) }}</span>
            <Slot
              v-for="slot in findSlots(dayIndex + 1, hour)"
              source="schedule"
              :key="`slot-${slot.id}`"
              :current-data="slots"
              :slot-data="slot"
              :date="slot.dateByTimezone.format('YYYY-MM-DD')"
              :first-day="firstDay"
              :popin-create-dispo-config="popinCreateDispoConfig"
              :popin-delete-config="popinDeleteConfig"
              :start="start"
              :end="end"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import { SlotsModel, SnpPopinConfigModel } from '@/models';
import Slot from '@/components/sharedComponents/Slot.component.vue';

dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);

export default {
  components: {
    // eslint-disable-next-line vue/no-reserved-component-names
    Slot,
  },
  props: {
    start: {
      type: String,
      required: true,
      validator: (date) => {
        // Check if date is valid, and if it is Monday, as required.
        const dateObj = dayjs(date);
        if (Number.isNaN(dateObj.day())) return false;
        return dateObj.day() === 1;
      },
    },
    end: {
      type: String,
      required: true,
    },
    popinDeleteConfig: {
      type: Object,
      default: () => ({}),
    },
    slots: {
      type: SlotsModel,
      required: true,
    },
    popinCreateDispoConfig: {
      type: SnpPopinConfigModel,
      default: new SnpPopinConfigModel(),
    },
    unavailableDates: {
      type: Object,
      required: false,
      default: () => ({}),
    },
  },
  setup(props) {
    const table = ref(null);
    const baseDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    const days = computed(() => baseDays.map((d, i) => ({
      dayNumber: firstDay.value.add(i, 'day').date(),
      fullDate: firstDay.value.add(i, 'day'),
    })));
    const defaultTimezone = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';
    const firstDay = computed(() => dayjs(props.start).tz(defaultTimezone));
    const firstDayNb = computed(() => firstDay.value.date());

    function findSlots(dayIndex, hour) {
      // get slots on this day and has same start hours.
      // recurring and dated.
      return props.slots.getSlots().filter((slot) => (
        slot.day === (dayIndex % 7) && slot.startDate.hour() === hour
      ));
    }

    function getTime(i) {
      return `${String(i).padStart(2, '0')}:00`;
    }

    /**
     * @description To apply a class to the cell of table if there are unavailabities
     * @param day {Object}
     */
    function getUnavailableStyle(day) {
      const today = dayjs().utc(true).format();
      const cellDay = dayjs(day).utc(true).format();
      let isUnavailable = false;
      if (props.unavailableDates) {
        if (props.unavailableDates.vacationMode) {
          if (dayjs(cellDay).isSameOrAfter(today, 'day')) {
            isUnavailable = true;
          }
        } else if (props.unavailableDates.dates) {
          isUnavailable = props.unavailableDates.dates.find((el) => {
            const startDay = dayjs(el.value).format();
            const endDay = dayjs(el.end_value).format();
            return dayjs(cellDay).isBetween(startDay, endDay, 'day', '[]') || false;
          });
        }
      }
      return isUnavailable;
    }

    return {
      table,
      firstDay,
      firstDayNb,
      baseDays,
      days,
      getTime,
      findSlots,
      getUnavailableStyle,
    };
  },
};

</script>
