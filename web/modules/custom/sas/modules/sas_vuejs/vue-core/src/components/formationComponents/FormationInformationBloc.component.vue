<template>
  <div class="formation-bloc-info d-flex">
    <i
      v-for="(icon, idx) in currentIcons"
      :class="`fa-solid fa-${icon.icon_name}`"
      :key="`fas-icon-${idx}`"
    />
    <div v-html="$sanitize(currentText)" />
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  props: {
    component: {
      type: String,
      default: 'InformationBloc',
    },
    content: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: 'info',
    },
  },
  setup(props) {
    const currentText = computed(() => props.content.text);
    const currentIcons = computed(() => props.content.icon);

    return {
      currentText,
      currentIcons,
    };
  },
};
</script>
