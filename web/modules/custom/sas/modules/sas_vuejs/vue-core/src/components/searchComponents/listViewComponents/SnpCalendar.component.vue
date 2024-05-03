<template>
  <div v-if="cardData && !cardData.doNotShowSlots">
    <div>
      <div>
        <!-- Hours Section -->
        <div class="consultation-table">
          <div class="wrapper availabilities-calendar">
            <SnpCalendarSection
              :day-val="showAll ? todayVal : todayVal.slice(0, limitCount)"
              :day-name="'today'"
              :current-data="cardData"
              :limit-Count="limitCount"
              @open-modal-orientation="$emit('open-modal-orientation', $event)"
            />
            <SnpCalendarSection
              :day-val="showAll ? tomorrowVal : tomorrowVal.slice(0, limitCount)"
              :day-name="'tomorrow'"
              :current-data="cardData"
              :limit-Count="limitCount"
              @open-modal-orientation="$emit('open-modal-orientation', $event)"
            />
            <SnpCalendarSection
              :day-val="showAll ? afterTomorrowVal : afterTomorrowVal.slice(0, limitCount)"
              :day-name="'afterTomorrow'"
              :current-data="cardData"
              :limit-Count="limitCount"
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
              Voir plus de créneaux
            </button>

            <button
              v-else
              class="btn-link"
              type="button"
              @click.prevent="showAll = false"
            >
              Voir moins de créneaux
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
  computed,
  onMounted,
  ref,
  watch,
} from 'vue';
import {
  CalendarSlotListModel,
} from '@/models';
import SnpCalendarSection from '@/components/searchComponents/listViewComponents/SnpCalendarSection.component.vue';

export default {
  props: {
    cardData: { type: Object, default: () => ({}) },
    currentIndex: { type: Number, default: 0 },
  },
  emits: [
    'open-modal-orientation',
  ],
  components: { SnpCalendarSection },
  setup(props) {
    const currentCardIndex = ref(props.currentIndex);
    const limitCount = ref(4);
    const showAll = ref(false);
    const todayVal = ref([]);
    const tomorrowVal = ref([]);
    const afterTomorrowVal = ref([]);
    const showModal = ref(false);
    const selectedSlot = ref({});
    const calendarSlotList = new CalendarSlotListModel(props.cardData);

    /**
     * showMoreSlots
     */
    const showMoreSlots = computed(() => {
      if (!props.cardData.slotList) {
        return false;
      }

      return (
        todayVal.value.length > limitCount.value
        || tomorrowVal.value.length > limitCount.value
        || afterTomorrowVal.value.length > limitCount.value
      );
    });

    /**
     * fetch slot list data from CalendarSlotListModel
     */
    const fetchSlotList = () => {
      const slotList = calendarSlotList.getSlotList();

      todayVal.value = slotList.today || [];
      tomorrowVal.value = slotList.tomorrow || [];
      afterTomorrowVal.value = slotList.afterTomorrow || [];
    };

    onMounted(() => {
      fetchSlotList();
    });

    watch(() => props.cardData, () => {
      fetchSlotList();
    });

    return {
      currentCardIndex,
      limitCount,
      todayVal,
      tomorrowVal,
      afterTomorrowVal,
      showModal,
      selectedSlot,
      showMoreSlots,
      fetchSlotList,
      showAll,
    };
  },
};
</script>
