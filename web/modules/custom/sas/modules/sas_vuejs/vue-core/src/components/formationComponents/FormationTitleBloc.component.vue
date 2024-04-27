<template>
  <h2 class="formation-subtitle">{{ currentTitle }}</h2>
</template>

<script>
import { computed } from 'vue';

export default {
  props: {
    component: {
      type: String,
      default: 'TitleBloc',
    },
    content: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: 'title',
    },
  },
  setup(props) {
    const currentTitle = computed(() => props.content.title);

    return {
      currentTitle,
    };
  },
};
</script>
