<template>
  <button type="button" @click="open = true">
    <i class="sas-icon sas-icon-information" aria-hidden="true" />
    <span class="sr-only">Information sur la plage SNP</span>
  </button>
  <ModalWrapper v-if="open" @on-close-modal="open = false" title="Information sur la plage disponible" modal-class="modal-info-slot">
    <p class="desc">Pour cette plage, vous proposez :</p>
    <ul class="resetul list-time-slots-snp">
      <li v-for="modality in modalities" :key="modality">
        {{getTraduction(modality)}}
      </li>
    </ul>
    <p>Vous pouvez modifier vos modalités de consultation en utilisant le pictogramme crayon.</p>
  </ModalWrapper>
</template>

<script>
import { ref } from 'vue';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import { SnpPopinConfigModel } from '@/models';

export default {
  components: { ModalWrapper },
  props: {
    modalities: {
      type: Array,
      default: () => [],
    },
    config: {
      type: SnpPopinConfigModel,
      default: () => new SnpPopinConfigModel(),
    },
  },
  setup(props) {
    const open = ref(false);
    function getTraduction(name) {
      let key = '';

      if (name === 'home') key = 'home';
      else if (name === 'teleconsultation') key = 'teleconsultation';
      else if (name === 'physical') key = 'office';

      return props.config.group4[key];
    }
    return {
      open,
      getTraduction,
    };
  },
};
</script>
