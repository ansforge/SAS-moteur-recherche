<template>
  <div class="db-informations">
    <div class="db-address-title">
      <h3>
        <i class="sas-icon sas-icon-pencil" aria-hidden="true" /> Informations
        complémentaires
      </h3>

      <PopinInfo
        buttonTitle="Éditer"
        :sheetNid
        :userIdNat
        :isDisabled
        :isAggregOnlyAddress
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
import { ref } from 'vue';
import PopinInfo from '@/components/calendars/PopinInfo.component.vue';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';

export default {
  components: {
    PopinInfo,
    RingLoader,
  },
  props: {
    sheetNid: {
      type: Number,
      default: null,
    },
    userIdNat: {
      type: String,
      default: '',
    },
    isDisabled: {
      type: Boolean,
      default: false,
    },
    isAggregOnlyAddress: {
      type: Boolean,
      default: false,
    },
  },
  setup() {
    const additionalInformation = ref('');

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
    };
  },
};
</script>
