<template>
  <div
    class="formation-bloc-file d-flex">
    <i class="icon icon-download" />
    <a :href="$sanitizeUrl(fileUrl)" download="download">Télécharger: {{ fileName }}</a>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  props: {
    component: {
      type: String,
      default: 'FileBloc',
    },
    content: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: 'file',
    },
  },
  setup(props) {
    const fileUrl = computed(() => props.content.file);
    const fileName = computed(() => {
      const currentName = decodeURI(fileUrl.value.split('/').at(-1));
      return currentName.length > 25 ? `${currentName.slice(0, 25)}...` : currentName;
    });

    return {
      fileUrl,
      fileName,
    };
  },
};
</script>
