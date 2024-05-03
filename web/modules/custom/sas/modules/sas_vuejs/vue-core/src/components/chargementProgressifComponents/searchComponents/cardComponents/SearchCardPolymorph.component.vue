<template>
  <div
    class="search-card"
    :class="[
      `search-card-${mode}`,
    ]"
  >
    <div class="search-card-grid-col">
      <slot name="header" />
      <template v-if="mode === 'compact'">
        <slot name="address" />
      </template>
      <slot name="surnumerary" :emitButtonClick />
    </div>

    <div class="search-card-grid-col">
      <template v-if="mode === 'compact'">
        <div class="search-card-grid-top-right-zone">
          <slot name="phoneNumber" :phoneNumber />
          <slot name="generalPractitioner" />
        </div>
      </template>
      <template v-if="mode === 'long'">
        <slot name="phoneNumber" :phoneNumber />
        <slot name="address" />
      </template>
      <slot name="additional" />
      <template v-if="mode === 'compact'">
        <slot name="calendar" />
      </template>
    </div>

    <template v-if="mode === 'long'">
      <div class="search-card-grid-col">
        <div class="search-card-grid-top-right-zone">
          <slot name="generalPractitioner" />
        </div>
        <slot name="calendar" />
      </div>
    </template>

    <div class="search-card-other-zone">
      <!--orientation modal-->
      <OrientationModal
        v-if="showOrientationModal"
        :cardData
        :type="cardTypeOrientation"
        @close="$emit('close-orientation-modal')"
      />
    </div>
  </div>
</template>

<script>

import {
  computed,
} from 'vue';

import OrientationConst from '@/const/orientation.const';
import OrientationModal from '@/components/searchComponents/orientationModal/OrientationModal.component.vue';

/**
 * This component is used in collaboration with `SearchCard`.
 * It places the slots provided by `SearchCard` to form the right layout depending of the mode (compact or long).
 * It also handles the surnumerary orientation modal.
 */
export default {
  name: 'SearchCardPolymorph',
  components: {
    OrientationModal,
  },
  props: {
    cardData: {
      type: Object,
      default: () => ({}),
    },
    mode: {
      type: String,
      required: true,
    },
    showOrientationModal: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    'open-orientation-modal',
    'close-orientation-modal',
    'go-to-cpts-page',
  ],
  setup(props, { emit }) {
    const emitButtonClick = () => {
      emit('open-orientation-modal');
    };

    const phoneNumber = computed(() => props.cardData?.final_phone_number);

    return {
      cardTypeOrientation: OrientationConst.SURNUMERAIRE,
      emitButtonClick,
      phoneNumber,
    };
  },
};
</script>
