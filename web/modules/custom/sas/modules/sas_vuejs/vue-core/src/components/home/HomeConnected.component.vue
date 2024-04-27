<template>
  <div class="sas-home-wrapper-connected" :style="backgroundImage">
    <div class="hero-banner-content">
      <h1 class="sas-home-main-title" v-html="$sanitize(description)" />
      <h2 v-if="subDescription" class="sas-home-main-subtitle" v-html="$sanitize(subDescription)" />
      <SearchEngine />
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import SearchEngine from '@/components/chargementProgressifComponents/searchComponents/SearchEngine.component.vue';

export default {
  name: 'HomeConnected',
  components: {
    SearchEngine,
  },
  props: {
    description: { type: String, default: '' },
    subDescription: { type: String, default: '' },
    bgImage: { type: String, default: '' },
    bgImageMobile: { type: String, default: '' },
  },
  setup(props) {
    const backgroundImage = computed(() => (props.bgImage ? `background-image: url(${props.bgImage});` : ''));

    return {
      backgroundImage,
    };
  },
};
</script>
