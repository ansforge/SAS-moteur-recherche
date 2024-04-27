<template>
  <div class="consultation-table">
    <div class="wrapper availabilities-calendar">
      <SnpCalendarSection
        :currentPage="currentPage"
        :day-val="showAll ? columns[0].slots : columns[0].slots.slice(0, limitCount)"
        :day-name="columns[0].label"
        :current-data="{}"
        :loading="loading"
        @open-modal-orientation="$emit('open-modal-orientation', $event)"
      />
      <SnpCalendarSection
        :currentPage="currentPage"
        :day-val="showAll ? columns[1].slots : columns[1].slots.slice(0, limitCount)"
        :day-name="columns[1].label"
        :current-data="{}"
        :loading="loading"
        @open-modal-orientation="$emit('open-modal-orientation', $event)"
      />
      <SnpCalendarSection
        :currentPage="currentPage"
        :day-val="showAll ? columns[2].slots : columns[2].slots.slice(0, limitCount)"
        :day-name="columns[2].label"
        :current-data="{}"
        :loading="loading"
        @open-modal-orientation="$emit('open-modal-orientation', $event)"
      />
    </div>

    <!-- View more Section if exists -->
    <div v-if="showMoreSlots" class="view-more-wrapper">
      <button
        v-if="!showAll"
        class="btn-link"
        type="button"
        @click.prevent="showAll = true"
      >
        Voir plus
      </button>

      <button
        v-else
        class="btn-link"
        type="button"
        @click.prevent="showAll = false"
      >
        Voir moins
      </button>
    </div>
  </div>
</template>

<script>
import {
 ref, computed, onMounted,
} from 'vue';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isoWeek from 'dayjs/plugin/isoWeek';
import updateLocale from 'dayjs/plugin/updateLocale';

import SnpCalendarSection from '@/components/searchComponents/listViewComponents/SnpCalendarSection.component.vue';

import { UserService } from '@/services';
import { useUserData } from '@/stores';

import { SlotsModel } from '@/models';

dayjs.locale('fr');
dayjs.extend(isoWeek);
dayjs.extend(updateLocale);

export default {
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    slots: {
      type: SlotsModel,
      required: true,
    },
    startDate: {
      type: String,
      required: true,
    },
    currentPage: {
      type: String,
      default: '',
    },
  },
  emits: [
    'open-modal-orientation',
  ],
  components: { SnpCalendarSection },
  setup(props) {
    const limitCount = ref(2);
    const showAll = ref(false);
    const userDataStore = useUserData();

    const columns = computed(() => {
      const res = [];

      for (let i = 0; i < 3; i += 1) {
        const dayVal = dayjs(props.startDate).add(i, 'days');
        const dayLabel = dayVal.format('ddd DD');

        res.push({
          date: dayVal.format('YYYY-MM-DD'),
          label: dayLabel.charAt(0).toUpperCase() + dayLabel.slice(1),
          slots: [],
        });
      }

      props.slots.getSlots().forEach((slot) => {
        const index = res.findIndex((e) => (dayjs(e.date).isoWeekday() % 7) === slot.day);

        if (index > -1) {
          res[index].slots.push(slot);
        }
      });

      // Sort each slots by start hours
      res.forEach((e) => e.slots.sort((a, b) => (a.startDate.hour() - b.startDate.hour())));

      return res;
    });
    const showMoreSlots = computed(() => columns.value.some((col) => col.slots.length > limitCount.value));

    onMounted(async () => {
      const res = await UserService.getCurrentUser();
      userDataStore.setCurrentUser(res);
    });

    return {
      columns,
      showMoreSlots,
      limitCount,
      showAll,
    };
  },
};
</script>
