<template>
  <slot v-if="loading" name="loading" />
  <slot v-else />
</template>

<script>
export default {
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
