<template>
  <template v-if="horaireTraite.length">
    <i v-if="displayIcon" class="icon icon-opened"><span class="sr-only">Horaires d'ouverture</span></i>

    <div class="form-group">
      <div>
        <button
          id="accordion-horaires"
          :aria-controls="`collapse-horaires-${currentId}`"
          :aria-expanded="isShow ? 'true' : 'false'"
          class="collapse-toggle vuejs-collapse-link tracking"
          data-track="horaires-toggle-v2"
          type="button"
          @click.prevent="isShow = !isShow"
        >
          <p v-if="open" class="status status--label status--open">Ouvert </p>
          <p v-else class="status status--label status--close">Fermé </p>

          <span class="status status--after">
            {{ nextTimeMsg }}
            <span class="time">{{ printTime(nextTime) }}</span>
          </span>

          <i class="icon" :class="[{ 'icon-up': isShow }, { 'icon-down': !isShow }]">
            <span class="sr-only">consulter les horaires</span>
          </i>
        </button>
      </div>

      <SlideUpDownRgaa
        :accordion-id="`collapse-horaires-${currentId}`"
        aria-labelledby="accordion-horaires"
        :active="isShow"
        :duration="500"
      >
        <div class="schedule">
          <ul class="timetable">
            <li
              v-for="(dayHoraires, keyDayHoraires) in horaireTraite"
              class="timetable-row"
              :key="keyDayHoraires"
            >
              <div class="day">{{ getDay(dayHoraires[0]) }}</div>
              <div class="hours">
                <span
                  v-for="(hour, keyHour) in dayHoraires"
                  class="hour"
                  :key="keyHour"
                >
                  {{ getHoursM(hour) }} - {{ getHoursS(hour) }}
                </span>
              </div>
            </li>
          </ul>
        </div>
      </SlideUpDownRgaa>
    </div>
  </template>
</template>

<script>
import {
  ref,
  watch,
  onMounted,
} from 'vue';

import SlideUpDownRgaa from '@/components/sharedComponents/plugins/SlideUpDownRgaa.vue';

export default {
  name: 'time-accordion',
  props: {
    cardId: {
      type: Number,
      default: null,
    },
    horaires: {
      type: Array,
      default: () => ([]),
    },
    tz: {
      type: String,
      default: '',
    },
    displayIcon: {
      type: Boolean,
      default: true,
    },
    formatData: {
      type: Object,
      default: () => ({}),
    },
  },
  components: { SlideUpDownRgaa },
  setup(props) {
    const currentId = ref(props.cardId);
    const currentHoraires = ref(props.horaires);
    const currentTz = ref(props.tz);
    const isShow = ref(false);
    const horaireTraite = ref([]);
    const open = ref(false);
    const nextTime = ref(null);
    const nextTimeMsg = ref('- Ouvre à');

    const printTime = (value) => {
      if (!value) return '';
      const hh = value.substr(0, value.length - 2);
      const mm = value.substr(-2);
      return `${hh}h${mm}`;
    };

    const getDay = (value) => {
      if (!value) return '';
      const day = value.split('|')[0];
      const daysTable = [
        'Dimanche',
        'Lundi',
        'Mardi',
        'Mercredi',
        'Jeudi',
        'Vendredi',
        'Samedi',
      ];
      return daysTable[day];
    };

    const getHoursM = (value) => {
      if (!value) return '';
      const startHour = value.split('|')[1];
      const hh = startHour.substr(0, startHour.length - 2);
      const mm = startHour.substr(-2);
      return `${hh}h${mm}`;
    };

    const getHoursS = (value) => {
      if (!value) return '';
      const startHour = value.split('|')[2];
      const hh = startHour.substr(0, startHour.length - 2);
      const mm = startHour.substr(-2);
      return `${hh}h${mm}`;
    };

    function getSchedule() {
      horaireTraite.value = props.formatData.horaireTraite;
      open.value = props.formatData.open;
      nextTime.value = props.formatData.nextTime;
      nextTimeMsg.value = props.formatData.nextTimeMsg;
    }

    onMounted(getSchedule);

    watch(() => [props.tz, props.cardId], () => {
      currentHoraires.value = props.horaires;
      currentTz.value = props.tz;
      currentId.value = props.cardId;
    });

    return {
      currentId,
      currentHoraires,
      currentTz,
      isShow,
      horaireTraite,
      open,
      nextTime,
      nextTimeMsg,
      printTime,
      getDay,
      getHoursS,
      getHoursM,
      getSchedule,
    };
  },
};
</script>
