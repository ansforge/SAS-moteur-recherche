<template>
  <component
    :is="source === 'schedule' ? 'div' : 'tr'"
    :class="slotClass"
    :data-slot-id="slotData.id"
    :style="source === 'schedule' ? generateSlotStyle(slotData) : ''"
    :tabindex="source === 'schedule' ? '-1' : '0'"
    role="button"
    @click="openModal = true"
  >
    <component :is="source === 'schedule' ? 'div' : 'td'">
      <!--        Schedule's slot          -->
      <template v-if="source === 'schedule'">
        <div class="wrapper-slot-options">
          <PopinInfoSlot
            v-if="slotData.modalities.length"
            :modalities="slotData.modalities"
            :config="popinCreateDispoConfig"
          />

          <template v-if="!isSlotModifiable">
            <PopinUpdateDispo
              :config="popinCreateDispoConfig"
              :current-slot="slotData"
              :date="date"
              @submited="fetchSlots(start, end)"
            >
              <template v-slot="{ openModal }">
                <button class="js-btn-open-modal-sas" type="button" @click="openModal">
                  <i class="icon icon-pencil" aria-hidden="true" />
                  <span class="sr-only">Ajouter ou modifier des disponibilités</span>
                </button>
              </template>
            </PopinUpdateDispo>

            <PopinDeleteSlot
              :date="date"
              :type="slotData.type"
              :id="slotData.id"
              :title="popinTitle"
              :subtitle="popinSubtitle"
              @submit="fetchSlots(start, end)"
            />
          </template>
        </div>
        <span>{{ getText(slotData) }}</span>
      </template>

      <!--        Search or Deep page's slot          -->
      <template v-else>
        <!--        AGGREGATOR          -->
        <span
          v-if="
            slotData.isAggregator
              && slotData.slot_reservation_link
              && currentUser.isRegulateurOSNPorIOA
          "
          class="slot-header"
        >
          <span class="slot-header">
            <a class="slot-header-link" :href="$sanitizeUrl(slotData.slot_reservation_link)" target="_blank" rel="noopener noreferrer">
              {{ slotData.time }}
            </a>
          </span>
        </span>

        <!--        SAS slot          -->
        <span v-else>
          <span class="slot-header">{{ slotData.time }}</span>
          <!--Span for hover-->
          <span
            v-if="!slotData.isAggregator && (currentUser.isRegulateurOSNPorIOA)"
            class="slot-header-hover"
          >
            Ajouter un patient
          </span>
        </span>
      </template>

      <template v-if="source !== 'schedule' || source !== 'deepPage'">
        <!--        COUNTER PLAGE          -->
        <div v-if="(slotData.max_patients && slotData.max_patients !== -1)">
          <!--        MODALITES SNP          -->
          <div class="slot-content">
            <div class="slot-legend has-counter">
              <ul class="resetul">

                <!--orientation count-->
                <li class="super-counter-wrap">
                  <div v-if="orientationCount.showCount" class="super-counter">
                    <span>{{ orientationCount.labelCount }}</span>
                  </div>
                </li>

                <!--modalities-->
                <li v-for="modality in slotData.modalite" class="tag-letter" :key="modality">
                  {{ modality }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!--slot legend-->
        <div v-else class="slot-content">
          <div class="slot-legend">
            <ul class="resetul">
              <li v-for="modality in slotData.modalite" class="tag-letter" :key="modality">
                {{ modality }}
              </li>
            </ul>
          </div>
        </div>
      </template>
    </component>

    <OrientationModal
      v-if="showModalOrientation"
      v-bind="orientationData"
      @close="openModal = false"
    />
  </component>
</template>

<script>
import { inject, computed, ref } from 'vue';

import dayjs from 'dayjs';
import isBetween from 'dayjs/plugin/isBetween';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import PopinInfoSlot from '@/components/calendars/PopinInfoSlot.component.vue';
import PopinDeleteSlot from '@/components/calendars/PopinDeleteSlot.component.vue';
import PopinUpdateDispo from '@/components/calendars/PopinUpdateDispo.component.vue';
import OrientationModal from '@/components/searchComponents/orientationModal/OrientationModal.component.vue';

import { SnpPopinConfigModel } from '@/models';
import { convertToSeconds } from '@/helpers';
import OrientationConst from '@/const/orientation.const';

import 'dayjs/locale/fr';

dayjs.extend(isBetween);
dayjs.extend(isSameOrAfter);
dayjs.extend(isSameOrBefore);

export default {
  components: {
    PopinInfoSlot,
    PopinDeleteSlot,
    PopinUpdateDispo,
    OrientationModal,
  },
  props: {
    slotData: {
      type: Object,
      default: () => ({}),
    },
    currentData: {
      type: Object,
      default: () => ({}),
    },
    currentUser: {
      type: Object,
      default: () => ({}),
    },
    source: {
      type: String,
      default: 'not_schedule',
    },
    date: {
      type: String,
      default: '',
    },
    firstDay: {
      type: Object,
      default: () => ({}),
    },
    popinCreateDispoConfig: {
      type: SnpPopinConfigModel,
      default: new SnpPopinConfigModel(),
    },
    start: {
      type: String,
      default: '',
    },
    end: {
      type: String,
      default: '',
    },
    popinDeleteConfig: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    // fetchSlots is call in schedule context to refresh slots after submit
    const fetchSlots = checkSourceFromSchedule() ? inject('fetchSlots') : undefined;

    const popinTitle = computed(() => (checkSourceFromSchedule() ? props.popinDeleteConfig.title : ''));

    const popinSubtitle = computed(() => (checkSourceFromSchedule() ? props.popinDeleteConfig.subtitle : ''));

    const columns = computed(() => (checkSourceFromSchedule() ? props.currentData.getColumns(props.firstDay) : {}));

    const openModal = ref(false);
    const showModalOrientation = computed(() => (
      openModal.value
      && props.currentUser?.isRegulateurOSNPorIOA
      && props.source !== 'deepPage'
      && !props.slotData.isAggregator
    ));

    const orientationData = computed(() => {
      const isPlage = props.slotData?.max_patients && props.slotData.max_patients !== -1;
      const type = isPlage ? OrientationConst.PLAGE : OrientationConst.CRENEAUX;

      return {
        cardData: props.currentData,
        calendarSlotData: props.slotData,
        type,
        open: openModal.value,
      };
    });

    function checkSourceFromSchedule() {
      return props.source === 'schedule';
    }

    // slot class handling
    const slotClass = computed(() => props.slotData.getSlotClass(props.currentUser, checkSourceFromSchedule()));

    // slot style handling
    function generateSlotStyle(slot) {
      const currentColumnSlots = columns.value[slot.getDateNumber(props.firstDay)] || [];
      const totalCollisions = Array.from(
        findCollisions(slot, currentColumnSlots),
      );

      totalCollisions.sort((a, b) => {
        if (a.startDate.hour() !== b.startDate.hour()) {
          return a.startDate.hour() - b.startDate.hour();
        }

        if (a.startDate.minute() !== b.startDate.minute()) {
          return a.startDate.minute() - b.startDate.minute();
        }

        return a.id - b.id;
      });

      const index = totalCollisions.findIndex((e) => e.id === slot.id);
      const position = index > -1 ? index + 1 : 1;

      return {
        width: `${100 / totalCollisions.length}%`,
        left:
          totalCollisions.length > 1 && position > 1
            ? `${(100 / totalCollisions.length) * (position - 1)}%`
            : '',
        height:
          `${(convertToSeconds(slot.endDate.hour(), slot.endDate.minute())
          - convertToSeconds(slot.startDate.hour(), slot.startDate.minute()))
          / 36}%`,
        top: `${convertToSeconds(0, slot.startDate.minute()) / 36}%`,
      };
    }

    function findCollisions(
      element,
      elements,
      acc = new Set([element]),
      elementIdsChecked = [element.id],
    ) {
      const currentFullStartDate = dayjs(
        element.getFullStartDate(props.firstDay),
      );
      const currentFullEndDate = dayjs(element.getFullEndDate(props.firstDay));
      const directCollisions = elements.filter(
        (e) => (
          dayjs(e.getFullStartDate(props.firstDay))
            .isBetween(currentFullStartDate, currentFullEndDate)
          || dayjs(e.getFullEndDate(props.firstDay))
            .isBetween(currentFullStartDate, currentFullEndDate)
          || (
              dayjs(e.getFullStartDate(props.firstDay))
                .isSameOrBefore(currentFullStartDate)
              && dayjs(e.getFullEndDate(props.firstDay))
                .isSameOrAfter(currentFullEndDate)
            )
        ),
      );

      directCollisions
        .filter((e) => !elementIdsChecked.includes(e.id))
        .forEach((e) => acc.add(e));
      elementIdsChecked.push(element.id);

      directCollisions.forEach((slot) => {
        if (!elementIdsChecked.includes(slot.id)) {
          const innerCollisions = findCollisions(
            slot,
            elements,
            acc,
            elementIdsChecked,
          );

          if (innerCollisions.length) {
            innerCollisions.forEach((e) => acc.add(e));
          }
        }
      });

      return acc;
    }

    // slot label
    function getText(slotItem) {
      return `${slotItem.startDate.format('HH')}h${slotItem.startDate.format(
        'mm',
      )} - ${slotItem.endDate.format('HH')}h${slotItem.endDate.format('mm')}`;
    }

    // slot modification
    const isSlotModifiable = computed(() => props.source === 'schedule' && props.slotData?.orientation_count > 0);

    const orientationCount = computed(() => {
      const showCount = (
        (props.source === 'not_schedule' || props.source === 'deepPage' || props.source === '')
        && props.currentUser?.isRegulateurOSNPorIOA
      );

      const labelCount = `${props.slotData?.orientation_count || 0}/${props.slotData?.max_patients}`;

      return {
        showCount,
        labelCount,
      };
    });

    return {
      getText,
      popinTitle,
      popinSubtitle,
      generateSlotStyle,
      fetchSlots,
      slotClass,
      isSlotModifiable,
      openModal,
      orientationData,
      orientationCount,
      showModalOrientation,
    };
  },
};
</script>
