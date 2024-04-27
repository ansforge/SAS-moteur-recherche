<template>
  <div class="formation-bottom-nav">
    <button
      v-if="hasPrevious"
      type="button"
      class="prev-article btn-highlight-outline"
      @click="$emit('go-to-previous')"
    >
      <i class="icon icon-left" aria-hidden="true" />
      Précédent
    </button>

    <button
      v-if="hasNext"
      type="button"
      class="next-article btn-highlight-outline"
      @click="$emit('go-to-next')"
    >
      Suivant
      <i class="icon icon-right" aria-hidden="true" />
    </button>
  </div>
</template>

<script>

export default {
  name: 'FormationPagination',
  props: {
    hasNext: {
      type: Boolean,
      default: false,
    },
    hasPrevious: {
      type: Boolean,
      default: false,
    },
  },
};
</script>
