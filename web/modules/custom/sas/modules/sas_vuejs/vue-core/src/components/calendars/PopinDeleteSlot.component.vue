<template>
  <button type="button" @click="open = true" class="js-btn-open-modal-sas">
    <span class="sr-only">Supprimer l'horaire de consultation SNP</span>
    <i class="sas-icon sas-icon-trash" aria-hidden="true" />
  </button>

  <ModalWrapper v-if="open" @on-close-modal="close" :title="title" modal-class="modal-delete-slot">
    <div>
      <div v-if="loading" class="simple-loader-backdrop">
        <div class="center-content-horizontal">
          <div class="loader-wrapper">
            <div class="lds-ring">
              <div />
              <div />
            </div>
          </div>
        </div>
      </div>
      <p v-if="subtitle">{{subtitle}}</p>
      <div>
        <div class="form-item radio-standard">
          <input
            type="radio"
            id="radio-remove-recurrence"
            value="all"
            name="removal-radio"
            v-model="picked"
            :disabled="type === 'dated'">
          <label for="radio-remove-recurrence">
            Supprimer le créneau ou la plage pour toutes les semaines à compter du {{dateFormatted}}
          </label>
        </div>

        <div class="form-item radio-standard">
          <input type="radio" id="radio-remove-specific" value="single" name="removal-radio" v-model="picked">
          <label for="radio-remove-specific">
            Supprimer cette occurence uniquement : {{dateFormatted}}
          </label>
        </div>
      </div>

      <div class="wrapper-btn-actions">
        <button class="btn-highlight-outline btn-cancel js-btn-cancel" type="button" @click="close">
          Annuler
        </button>
        <button class="btn-hightlight-outline form-submit" type="button" :disabled="loading" @click="deleteSlot">
          Enregistrer
        </button>
      </div>
    </div>
  </ModalWrapper>
</template>

<script>
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { ref, computed } from 'vue';
import { CalendarService } from '@/services';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';

export default {
  components: { ModalWrapper },
  emits: ['submit'],
  props: {
    date: {
      type: String,
      required: true,
    },
    type: {
      type: String, // recurring, dated
      required: true,
    },
    id: {
      type: Number,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    subtitle: {
      type: String,
      default: '',
    },
  },
  setup(props, { emit }) {
    const open = ref(false);
    const loading = ref(false);
    const picked = ref('single'); // single, all

    function close() {
      picked.value = 'single';
      open.value = false;
    }

    const dateFormatted = computed(() => dayjs(props.date).format('DD/MM/YYYY'));

    async function deleteSlot() {
      loading.value = true;
      const disableAllOccurences = picked.value === 'all';
      try {
        await CalendarService.deleteSlot(CalendarService.getNodeId(), {
          id: props.id,
          type: props.type,
          disableAllOccurences,
          date: dayjs(props.date).format('YYYY-MM-DDTHH:mm:ssZ'),
        });
        emit('submit');
        close();
      } catch (e) {
        console.error('Error deleting slot', e);
      } finally {
         loading.value = false;
      }
    }

    return {
      open,
      loading,
      picked,
      dateFormatted,
      close,
      deleteSlot,
    };
  },
};
</script>
