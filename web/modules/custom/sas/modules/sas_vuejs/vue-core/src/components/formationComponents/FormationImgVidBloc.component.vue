<template>
  <div
    class="formation-bloc-media"
    :class="[
      { 'content-img': currentType === 'img' },
      { 'content-vid': currentType === 'vid' },
    ]"
  >
    <h2 v-if="currentTitle.length" class="formation-subtitle">{{ currentTitle }}</h2>

    <img
      v-if="currentType === 'img'"
      :src="contentUrl"
      class="img-full"
      alt=""
    />

    <iframe
      v-if="currentType === 'vid'"
      :id="`player-${currentTitle}`"
      type="text/html"
      :title="currentTitle"
      :width="contentDimensions.width"
      :height="contentDimensions.height"
      allowfullscreen="1"
      :src="contentUrl"
    />
  </div>
</template>

<script>
import { computed } from 'vue';
import { videoUrlConvert } from '@/helpers';

export default {
  props: {
    component: {
      type: String,
      default: 'ImgVidBloc',
    },
    content: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: 'imgvid',
    },
  },
  setup(props) {
    const currentTitle = computed(() => props.content.title);
    const currentType = computed(() => props.content.type);
    const contentUrl = computed(() => {
      if (currentType.value === 'vid') {
        return videoUrlConvert.streamingPlatformEmbedUrlGenerator(props.content.vid);
      }

      return props.content.img;
    });
    const contentDimensions = computed(() => ({ width: props.content.width || '640', height: props.content.height || '360' }));

    return {
      currentTitle,
      currentType,
      contentUrl,
      contentDimensions,
    };
  },
};
</script>
