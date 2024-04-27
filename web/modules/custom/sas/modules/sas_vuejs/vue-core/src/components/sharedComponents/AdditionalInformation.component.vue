<template>
  <div class="additional-information-wrapper" :class="triggerMode === 'title' ? 'trigger-title' : 'trigger-viewmore'">
    <button
      v-if="triggerMode === 'title'"
      type="button"
      id="accordion-additional-information"
      :aria-controls="`collapse-additional-information-${cardId}`"
      :aria-expanded="!isPreview ? 'true' : 'false'"
      class="collapse-toggle vuejs-collapse-link tracking"
      @click.prevent="isPreview = !isPreview"
    >
      <strong class="additional-information-title">{{ additionalInfoTitle }}</strong>
      <i
        v-if="showArrowBtn"
        class="icon"
        :class="[{ 'icon-up': !isPreview }, { 'icon-down': isPreview }]"
      >
        <span class="sr-only">consulter les {{ additionalInfoTitle }}</span>
      </i>
    </button>
    <SlideUpDownRgaa
      :accordion-id="`collapse-additional-information-${cardId}`"
      aria-labelledby="accordion-additional-information"
      :active="!isPreview"
      :duration="350"
      :use-hidden="false"
      :initial-height="initialHeight"
    >
      <div
        class="additional-information-text"
        :class="{ open: !isPreview && additionalInfo.length > 100 }"
        ref="infoContainer"
      >
        <div class="additional-information-content">
          <p v-if="isString">
            {{ previewInfo }}
          </p>

          <template v-else>
            <ul class="additional-skills-list resetul">
              <li v-for="(label, idx) in previewInfo" :key="`label-${idx}`">{{ label }}</li>
            </ul>
          </template>
        </div>

        <button
          v-if="triggerMode === 'viewmore' && showArrowBtn"
          :aria-controls="`collapse-additional-information-${cardId}`"
          :aria-expanded="!isPreview"
          :aria-label="btnAriaLabel"
          class="collapse-toggle vuejs-collapse-link tracking"
          type="button"
          @click.prevent="isPreview = !isPreview"
        >
          <template v-if="isPreview">
            Voir plus
          </template>
          <template v-else>
            Voir moins
          </template>
        </button>
      </div>
    </SlideUpDownRgaa>
  </div>
</template>
<script>

import { ref, computed } from 'vue';
import SlideUpDownRgaa from '@/components/sharedComponents/plugins/SlideUpDownRgaa.vue';

export default {
  components: {
    SlideUpDownRgaa,
},
  props: {
    additionalInfo: {
      type: [Array, String],
      default: () => ([]),
    },
    cardId: {
      type: Number,
      default: null,
    },
    additionalInfoTitle: {
      type: String,
      default: '',
    },
    triggerMode: {
      type: String,
      default: 'title',
    },
  },
  setup(props) {
    const isString = computed(() => typeof props.additionalInfo === 'string');

    const isPreview = ref(true);

    const previewInfo = computed(() => {
      if (isString.value) {
        return (isPreview.value && props.additionalInfo.length > 100)
        ? `${props.additionalInfo.slice(0, 100)}...`
        : props.additionalInfo;
      }

      return (isPreview.value && props.additionalInfo.length > 2)
      ? props.additionalInfo.slice(0, 2)
      : props.additionalInfo;
    });

    const showArrowBtn = computed(() => (
      (isString.value && props.additionalInfo.length > 100)
      || (!isString.value && props.additionalInfo.length > 2)
    ));

    const infoContainer = ref(null);
    const initialHeight = computed(() => Math.min(infoContainer.value?.offsetHeight ?? 0, 103).toString() || '0');

    const btnAriaLabel = computed(() => `Afficher ${isPreview.value ? 'plus' : 'moins'} d'informations`);

    return {
      isString,
      infoContainer,
      isPreview,
      initialHeight,
      previewInfo,
      showArrowBtn,
      btnAriaLabel,
    };
  },
};
</script>
