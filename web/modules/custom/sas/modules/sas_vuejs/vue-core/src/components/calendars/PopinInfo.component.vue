<template>
  <!-- shouldn't be here-->
  <button
    v-if="!isAggregOnlyAddress"
    class="btn-highlight js-btn-open-modal-sas"
    :disabled="isDisabled"
    type="button"
    @click="open = true"
  >
    {{ buttonTitle }}
  </button>
  <!---->

  <ModalWrapper v-if="open" @on-close-modal="close" title="Informations complémentaires" modal-class="modal-further-info">
    <div>
      <div class="form-errors errors" v-if="errors.length">
        <ul class="resetul">
          <li v-for="(error, i) in errors" :key="i">
            {{ error }}
          </li>
        </ul>
      </div>
      <div v-if="loading" class="simple-loader-backdrop">
        <RingLoader />
      </div>
      <div class="form-type-textarea">
        <label for="edit-information" class="txt-bold-big">Ajouter des informations complémentaires :</label>
        <p class="further information-description">Ajout d'informations complémentaires à destination de la régulation médicale</p>
        <textarea class="form-textarea" name="edit_information_text" id="edit-information" cols="60" rows="5" v-model="text" @input="checkText" />
      </div>

      <div class="block-message" v-if="message.length">
        <div class="wrapper-wysiwyg" v-html="message" />
      </div>
      <div class="wrapper-btn-actions">
        <button
          type="button"
          class="btn-highlight-outline btn-cancel js-btn-cancel"
          @click="close"
        >
          Annuler
        </button>

        <button
          class="btn-hightlight-outline form-submit"
          :class="{ disabled: loading }"
          :disabled="loading || !isValid"
          type="button"
          @click="submit"
        >
          Enregistrer
        </button>
      </div>
    </div>
  </ModalWrapper>
</template>

<script>

import {
  onMounted, ref, reactive, computed,
} from 'vue';

import { CalendarService } from '@/services';
import { useUserDashboard } from '@/stores';

import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';

export default {
  emits: [
    'update-additional-info',
    'show-loader',
  ],
  components: {
    ModalWrapper,
    RingLoader,
  },
  props: {
    buttonTitle: {
      type: String,
      default: 'Informations complémentaires',
    },
    sheetNid: {
      type: Number,
      default: null,
    },
    userIdNat: {
      type: String,
      default: '',
    },
    source: {
      type: String,
      default: 'calendar',
    },
    isDisabled: {
      type: Boolean,
      default: false,
    },
    isAggregOnlyAddress: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { emit }) {
    const open = ref(false);
    const loading = ref(false);
    const text = ref('');
    const emptyText = ref(false);
    const message = ref('');
    const isValid = ref(false);
    const errors = reactive([]);

    const userDashboardStore = useUserDashboard();
    const isSosMedecinsChecked = computed(() => userDashboardStore.isSosMedecinsChecked);

    const isCalendar = props.source === 'calendar';
    const nodeId = computed(() => {
      if (!isCalendar) {
        return props.sheetNid;
      }

      return window?.API?.['time-slot-schedule']?.sheet_nid ?? null;
    });
    const idNat = computed(() => {
      if (!isCalendar) {
        return props.userIdNat;
      }

      return window?.API?.['time-slot-schedule']?.id_nat ?? null;
    });

    function checkText() {
      // if empty text : disable submit
      if (text.value.length && !text.value?.trim().length) {
        isValid.value = false;
        // with length but empty text : push error once
        if (!emptyText.value) {
          errors.splice(0);
          emptyText.value = true;
          errors.push('Le texte doit contenir au moins un caractère');
        }
      } else {
        errors.splice(0);
        emptyText.value = false;
        isValid.value = true;
      }
    }

    // For dashboard only
    function updateAdditionalInfo() {
      emit('update-additional-info', {
        additionalInfo: text.value,
      });
    }

    function emitLoaderHandler(status) {
      emit('show-loader', { status });
    }

    onMounted(async () => {
      loading.value = true;

      if (!isCalendar) {
        emitLoaderHandler(true);
      }

      if (
        !isSosMedecinsChecked.value
        && nodeId.value
      ) {
        text.value = await CalendarService.fetchAdditionalInformationText(nodeId.value, idNat.value);
        message.value = await CalendarService.fetchAdditionalInformationAlertMsg(nodeId.value);

        if (!isCalendar) {
          updateAdditionalInfo();
        }
      }

      loading.value = false;

      if (!isCalendar) {
        emitLoaderHandler(false);
      }
    });

    async function close() {
      open.value = false;
      errors.splice(0);

      if (!isCalendar) {
        emitLoaderHandler(true);
      }

      if (
        !isSosMedecinsChecked.value
        && nodeId.value
      ) {
        text.value = await CalendarService.fetchAdditionalInformationText(nodeId.value, idNat.value);
      }

      if (!isCalendar) {
        updateAdditionalInfo();
        emitLoaderHandler(false);
      }
    }

    async function submit() {
      errors.splice(0);
      checkText();
      loading.value = true;

      if (isValid.value) {
        try {
          await CalendarService.submitAdditionalInformationText({
            nid: nodeId.value,
            additional_data: text.value,
            national_id: idNat.value,
          });

          close();
        } catch (e) {
          console.error('Fail to update information text', e);
        } finally {
          loading.value = false;
        }
      } else {
        close();
      }
    }

    return {
      open,
      loading,
      text,
      message,
      submit,
      close,
      isValid,
      checkText,
      errors,
      idNat,
    };
  },
};
</script>
