<template>
  <div :class="`search-card search-card-${mode}`">
    <div class="search-card-grid-col">
      <slot name="header" />
      <template v-if="mode === 'compact'">
        <slot name="address" />
      </template>
      <slot name="surnumerary" :emitButtonClick="emitButtonClick" />
    </div>

    <div class="search-card-grid-col">
      <template v-if="mode === 'compact'">
        <div class="search-card-grid-top-right-zone">
          <slot name="phoneNumber" />
          <slot name="generalPractitioner" />
        </div>
      </template>
      <template v-if="mode === 'long'">
        <slot name="phoneNumber" />
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
        :cardData="cardData"
        :type="cardTypeOrientation"
        @close="$emit('close-orientation-modal')"
      />
    </div>
  </div>
</template>

<script>
import OrientationConst from '@/const/orientation.const';

import OrientationModal from '@/components/searchComponents/orientationModal/OrientationModal.component.vue';

/**
 * This component is used in collaboration with `SearchCard`.
 * It places the slots provided by `SearchCard` to form the right layout depending of the mode (compact or long).
 * It also handles the surnumerary orientation modal.
 */
export default {
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
  ],
  setup(props, { emit }) {
    const emitButtonClick = () => {
      emit('open-orientation-modal');
    };

    return {
      cardTypeOrientation: OrientationConst.SURNUMERAIRE,
      emitButtonClick,
    };
  },
};
</script>
