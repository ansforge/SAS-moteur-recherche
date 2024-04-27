<template>
  <div class="skeleton-box" :style="`height:${height}; width: ${width}; margin-bottom: ${bottom}; ${customStyle}`" />
</template>

<script>

export default {
  props: {
    height: {
      type: String,
      default: '30px',
    },
    width: {
      type: String,
      default: '100%',
    },
    bottom: {
      type: String,
      default: '0px',
    },
    customStyle: {
      type: String,
      default: '',
    },
  },
};
</script>

<style scoped>
  .skeleton-box {
    display: block;
  }
</style>
