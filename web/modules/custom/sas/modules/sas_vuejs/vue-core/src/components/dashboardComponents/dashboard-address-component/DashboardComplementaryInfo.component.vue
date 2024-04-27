<template>
  <div class="db-informations">
    <div class="db-address-title">
      <h3>
        <i class="sas-icon sas-icon-pencil" aria-hidden="true" /> Informations
        complémentaires
      </h3>

      <PopinInfo
        buttonTitle="Éditer"
        :timeslotNid="timeslotNid"
        :isDisabled="isBtnDisabled"
        source="dashboard"
        @update-additional-info="getAdditionalInfo"
        @show-loader="handleLoader"
      />
    </div>

    <RingLoader v-if="showLoader" />

    <div
      v-else-if="!additionalInformation || !additionalInformation.length"
      class="db-empty"
    >
      <p>Éditer pour ajouter des informations complémentaires</p>
    </div>

    <div v-else>
      <p v-html="$sanitize(additionalInformation)" />
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import PopinInfo from '@/components/calendars/PopinInfo.component.vue';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';

export default {
  components: {
    PopinInfo,
    RingLoader,
  },
  props: {
    timeslotNid: {
      type: String,
      default: '',
    },
    isDisabled: {
      type: Boolean,
      default: false,
    },
  },
  setup(props) {
    const additionalInformation = ref('');

    const isBtnDisabled = computed(() => props.isDisabled || !props.timeslotNid || !props.timeslotNid.length);

    function getAdditionalInfo(val) {
      additionalInformation.value = val.additionalInfo;
    }

    // loader feature
    const showLoader = ref(true);

    function handleLoader(val) {
      showLoader.value = val.status;
    }

    return {
      additionalInformation,
      getAdditionalInfo,
      handleLoader,
      showLoader,
      isBtnDisabled,
    };
  },
};
</script>
