<template>
  <div id="reset-cluster" class="views-row cluster-displaying">
    <button class="reset-marker-click sas-icon-back" type="button" @click.prevent="returnBtnClick">Retour à la recherche</button>
    <div class="block-addr">Résultats à l'adresse : <span> {{ clusterAddress }} </span></div>
  </div>
</template>

<script>
export default {
  emits: ['return-to-list'],
  props: {
    clusterAddress: { type: String, default: '' },
  },
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
