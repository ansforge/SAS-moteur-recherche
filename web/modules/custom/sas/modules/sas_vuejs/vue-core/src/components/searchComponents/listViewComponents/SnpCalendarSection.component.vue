<template>
  <div class="item" :class="[setOrEmptyClass, { 'is-today': dayName === 'today' }]">
    <caption v-if="dayName === 'today'" class="table-title">AUJOURD'HUI</caption>
    <table class="resetul">
      <caption class="sr-only">Tableau des crénaux disponibles</caption>
      <tr class="title">
        <th>{{ fetchDayName() }}</th>
      </tr>
      <SkeletonWrapper :loading>
        <template #loading>
          <tr v-for="j in 2" :key="j" class="availabilities-slot" style="background-color: unset;">
            <Skeleton height="49px" custom-style="border-radius: 5px;" />
          </tr>
        </template>

        <template #default>
          <!-- If empty data -->
          <tr v-if="dayVal.length < 1" class="text-wrapper">
            <td class="text-empty">Pas de créneau disponible</td>
          </tr>

          <!-- If data exists -->
          <template v-if="dayVal.length > 0" class="resetul">
            <Slot
              v-for="item in dayVal"
              :source="currentPage"
              :slot-data="item"
              :key="`slot-${item.slotGuid ?? item.id}`"
              :current-data="currentData"
              :current-user="currentUser"
              @click="openModal(item)"
              @keydown="handleKeydown($event, item)"
            />
          </template>
        </template>
      </SkeletonWrapper>
    </table>
  </div>
</template>

<script>
import { computed } from 'vue';
import { CalendarDayNameModel } from '@/models';
import { KEYBOARD_CONST } from '@/const';
import { useUserData } from '@/stores';
import OrientationConst from '@/const/orientation.const';
import Skeleton from '@/components/sharedComponents/Skeleton/Skeleton.component.vue';
import SkeletonWrapper from '@/components/sharedComponents/Skeleton/Skeleton-wrapper.component.vue';
import Slot from '@/components/sharedComponents/Slot.component.vue';

export default {
  props: {
    dayVal: { type: Array, default: () => ([]) },
    dayName: { type: String, default: '' },
    currentData: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    currentPage: { type: String, default: '' },
  },
  components: { Skeleton, SkeletonWrapper, Slot },
  emits: ['open-modal-orientation'],
  setup(props, { emit }) {
    const userDataStore = useUserData();
    const currentUser = computed(() => (userDataStore.currentUser));

    /**
     * fetch day name to display in the calendar
     * @param day
     * @returns {*}
     */
    const fetchDayName = () => {
      if (['today', 'tomorrow', 'afterTomorrow'].includes(props.dayName)) {
        const snpCalendarDayNameModel = new CalendarDayNameModel(props.dayName);
        return snpCalendarDayNameModel.getDayNameAndDate();
      }
        return props.dayName;
    };

    const openModal = (item) => {
      if (!props.currentData.isAggregator && currentUser.value.isRegulateurOSNPorIOA) {
        const isPlage = item.max_patients && item.max_patients !== -1;
        const type = isPlage ? OrientationConst.PLAGE : OrientationConst.CRENEAUX;

        emit('open-modal-orientation', {
          type,
          cardData: props.currentData,
          calendarSlotData: item,
        });
      }
    };

    const handleKeydown = (event, item) => {
      const { keyCode } = event;
      switch (keyCode) {
        case KEYBOARD_CONST.enter:
        case KEYBOARD_CONST.space:
          openModal(item);
          break;
        default:
      }
    };

    const setOrEmptyClass = computed(() => (props.dayVal.length > 0 ? 'set' : 'empty'));

    return {
      fetchDayName,
      openModal,
      currentUser,
      handleKeydown,
      setOrEmptyClass,
    };
  },
};
</script>
