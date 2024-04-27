<template>
  <div>
    <HealthOfferSummary
      class="modal-summary-grid"
      v-bind="summary"
    />
    <RegistrationAdditionalInformation
      v-if="showAdditionalInfo({ ss_sas_additional_info: additionalInfo }, userDataStore.currentUser)"
      :additional-info="additionalInfo"
    />

    <component
      :is="componentName"
      v-bind="componentProps"
      @registration-updated="$emit('registrationUpdated', $event)"
      @close="$emit('close')" />

  </div>
</template>

<script>

import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { computed } from 'vue';
import OrientationConst from '@/const/orientation.const';
import { useSearchHelper } from '@/composables';
import { useUserData } from '@/stores';
import HealthOfferSummary from '@/components/sharedComponents/HealthOfferSummary.component.vue';
import RegistrationSurnumeraireTab from './registration/RegistrationSurnumeraireTab.vue';
import RegistrationCreneauxTab from './registration/RegistrationCreneauxTab.vue';
import RegistrationPlageTab from './registration/RegistrationPlageTab.vue';
import RegistrationAdditionalInformation from './registration/RegistrationAdditionalInformation.component.vue';

dayjs.locale('fr');

export default {
  name: 'RegistrationTab',
  emits: ['registrationUpdated', 'close'],
  components: {
    Notification,
    RegistrationSurnumeraireTab,
    RegistrationCreneauxTab,
    RegistrationPlageTab,
    RegistrationAdditionalInformation,
    HealthOfferSummary,
},
  props: {
    summary: {
      type: Object,
      default: () => ({}),
    },
    calendarSlotData: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: '',
    },
    date: {
      type: [Object, String],
      default: () => ({}),
    },
    update: {
      type: Boolean,
      default: false,
    },
    additionalInfo: {
      type: String,
      default: '',
    },
  },
  setup(props) {
    const {
      showAdditionalInfo,
    } = useSearchHelper();

    const userDataStore = useUserData();

    const componentName = computed(() => {
      switch (props.type) {
        case OrientationConst.SURNUMERAIRE: return RegistrationSurnumeraireTab.name;
        case OrientationConst.PLAGE: return RegistrationPlageTab.name;
        case OrientationConst.CRENEAUX: return RegistrationCreneauxTab.name;
        default: return null;
      }
    });

    const componentProps = computed(() => {
      switch (props.type) {
        case OrientationConst.SURNUMERAIRE:
          return { date: props.date, update: props.update };
        case OrientationConst.PLAGE:
        case OrientationConst.CRENEAUX:
          return { calendarSlotData: props.calendarSlotData };
        default:
          return null;
      }
    });

    return {
      componentName,
      componentProps,
      showAdditionalInfo,
      userDataStore,
    };
  },
};
</script>
