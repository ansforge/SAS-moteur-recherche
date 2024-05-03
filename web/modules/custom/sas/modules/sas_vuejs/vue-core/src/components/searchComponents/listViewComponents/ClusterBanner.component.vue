<template>
  <div id="reset-cluster" class="views-row cluster-displaying">
    <button class="reset-marker-click sas-icon-back" type="button" @click.prevent="returnBtnClick">
      Retour à la recherche
    </button>
    <div class="block-addr">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  emits: ['return-to-list'],
  setup(props, { emit }) {
    function returnBtnClick() {
      emit('return-to-list');
    }

    return {
      returnBtnClick,
    };
  },
};
</script>
