<template>
  <div
    class="formation-bloc-text"
    :class="[
      { 'content-right': mediaPos === 'left' },
      { 'content-left': mediaPos === 'right' },
    ]"
  >
    <h2 v-if="currentTitle.length" class="formation-subtitle">{{ currentTitle }}</h2>
    <div class="d-flex">
      <div v-html="$sanitize(currentText)" />

      <div v-if="hasMediaContent === 'img'" class="img-wrapper">
        <img :src="contentUrl" :alt="currentTitle" />
      </div>

      <iframe
        v-if="hasMediaContent === 'vid'"
        :id="`player-${currentTitle}`"
        type="text/html"
        :title="currentTitle"
        :width="contentDimensions.width"
        :height="contentDimensions.height"
        allowfullscreen="1"
        :src="contentUrl"
      />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import { videoUrlConvert } from '@/helpers';

export default {
  props: {
    component: {
      type: String,
      default: 'TextBloc',
    },
    content: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: 'text',
    },
  },
  setup(props) {
    const currentTitle = computed(() => props.content.title);
    const currentText = computed(() => props.content.text);
    const hasMediaContent = computed(() => props.content.type);
    const mediaPos = computed(() => props.content.pos || '');
    const contentUrl = computed(() => {
      if (hasMediaContent.value === 'vid') {
        return videoUrlConvert.streamingPlatformEmbedUrlGenerator(props.content.vid);
      }

      return props.content.img;
    });
    const contentDimensions = computed(() => ({ width: props.content.width || '640', height: props.content.height || '360' }));

    return {
      currentTitle,
      currentText,
      hasMediaContent,
      mediaPos,
      contentUrl,
      contentDimensions,
    };
  },
};
</script>
