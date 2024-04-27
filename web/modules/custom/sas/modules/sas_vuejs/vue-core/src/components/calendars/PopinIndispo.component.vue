<template>
  <button
    class="btn-highlight with-icon js-btn-open-modal-sas"
    :disabled="isDisabled"
    type="button"
    @click="open = true"
  >
    <i class="icon icon-pencil" aria-hidden="true" />
    {{ popinUnavailabilityTitle }}
  </button>

  <ModalWrapper v-if="open" @on-close-modal="close" title="Période d'indisponibilité" modal-class="modal-unavailability">
    <div>
      <div v-if="errors.length" class="form-errors errors">
        <ul class="resetul">
          <li v-for="error in errors" :key="error">
            {{error}}
          </li>
        </ul>
      </div>
      <h3 class="txt-bold-big">Vous êtes indisponible</h3>
      <div v-if="loading" class="simple-loader-backdrop">
        <div class="center-content-horizontal">
          <RingLabel />
        </div>
      </div>
      <div class="form-item form-type-checkbox checkbox-indefinite-period">
        <input type="checkbox" id="checkbox-unavailable-now" v-model="vacationMode">
        <label class="option" for="checkbox-unavailable-now">Dès maintenant pour une durée indéterminée</label>
      </div>

      <div class="form-item form-date-inline" v-for="(date, i) in dates" :key="i">
        <label for="edit-date">
          <strong>Du</strong>
        </label>
        <input id="edit-date" type="date" v-model="dates[i].from" :min="todayDate">

        <label for="edit-date2">
          <strong>Au</strong>
        </label>
        <input id="edit-date2" type="date" v-model="dates[i].to">

        <button @click="removeDateLine(i)" type="button" class="btn-delete">
          <span>Supprimer</span>
        </button>
      </div>

      <div class="wrapper-add-slot" v-if="dates.length < 3">
        <button @click="addDateLine" type="button" class="btn-add">
          <span>Ajouter une autre période d'indisponibilité</span>
        </button>
      </div>

      <p>
        En validant votre indisponibilité, vos disponibilités pour ce lieu d'exercice seront désactivées sur la plage sélectionnée et vous n'apparaitrez plus dans les résultats de recherche. Vos disponibilités seront réactivées automatiquement à la fin de la période d'indisponibilité sélectionnée. Vous devez déclarer l'indisponibilité individuellement sur chacun des lieux pour lesquels vous souhaitez désactiver vos disponibilités.
      </p>

      <div class="wrapper-btn-actions">
        <button class="btn-highlight-outline btn-cancel js-btn-cancel" type="button" @click="close">
          Annuler
        </button>
        <button class="btn-hightlight-outline form-submit" :class="{ disabled: loading }" :disabled="loading" type="button" @click="submit">
          Enregistrer
        </button>
      </div>

    </div>
  </ModalWrapper>
</template>

<script>
import dayjs from 'dayjs';
import { ref, computed } from 'vue';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import RingLabel from '@/components/sharedComponents/loader/RingLoader.component.vue';
import { CalendarService } from '@/services';
import { SnpIndispoModel } from '@/models';

export default {
  components: {
    ModalWrapper,
    RingLabel,
  },
  emits: [
    'submit',
    'update-unavailibities-info',
  ],
  props: {
    popinUnavailabilityTitle: {
      type: String,
      default: 'Programmer une indisponibilité',
    },
    timeslotNid: {
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
  },
  setup(props, { emit }) {
    const open = ref(false);
    const loading = ref(false);
    const dates = ref([]);
    const defaultSingleDate = { from: '', to: '' };
    const vacationMode = ref(false);
    const errors = ref([]);
    const todayDate = dayjs().format('YYYY-MM-DD');
    const isCalendar = props.source === 'calendar';
    const nodeId = computed(() => (isCalendar ? CalendarService.getNodeId() : props.timeslotNid));

    // For dashboard only
    function updateUnavailibities() {
      emit('update-unavailibities-info', {
        vacationMode: vacationMode.value,
        dates: dates.value,
      });
    }

    async function fetchIndispoAndApply() {
      if (nodeId.value) {
        const res = await CalendarService.getIndispoByNodeId(nodeId.value);
        const data = new SnpIndispoModel(res);
        vacationMode.value = data.vacationMode;
        dates.value = data.dates.map((date) => ({
          from: dayjs(date.value).format('YYYY-MM-DD'),
          to: dayjs(date['end_value']).format('YYYY-MM-DD'),
        }));
        if (!isCalendar) {
          updateUnavailibities();
        }
      }
    }

    fetchIndispoAndApply();

    function addDateLine() {
      if (dates.value.length < 3) {
        dates.value.push({ ...defaultSingleDate });
      }
    }

    function removeDateLine(index) {
      dates.value = dates.value.filter((date, i) => i !== index);
    }

    function close() {
      fetchIndispoAndApply();
      errors.value.length = 0;
      open.value = false;
    }

    function checkDates() {
      const invalid = 'La date de fin ne peut pas être antérieure à la date de début pour la période d\'indisponibilité';
      const missingStart = 'Une date de début est requise pour la période d\'indisponibilité';
      const missingEnd = 'Une date de fin est requise pour la période d\'indisponibilité';
      return dates.value.reduce((acc, curr, i) => {
        // check for incoherent dates
        if (dayjs(curr.to).isBefore(dayjs(curr.from))) {
          return [...acc, `${invalid} ${i + 1}`];
        }
        // check for missing start date
        if (!curr.from) {
          return [...acc, `${missingStart} ${i + 1}`];
        }
        // check for missing end date
        if (!curr.to) {
         return [...acc, `${missingEnd} ${i + 1}`];
        }
        return acc;
      }, []);
    }

    async function submit() {
      loading.value = true;
      const errorsFound = checkDates();
      if (errorsFound.length) {
        errors.value = errorsFound;
        loading.value = false;
        return;
      }
      const datesFormattedForApi = dates.value.map((date) => ({
          value: `${date.from}T00:00:00`,
          end_value: `${date.to}T23:59:59`,
        }));
      try {
        await CalendarService.postIndispo(nodeId.value, vacationMode.value, datesFormattedForApi);

        if (!isCalendar) {
          updateUnavailibities();
        } else {
          emit('submit');
        }

        close();
      } catch (e) {
        console.error('Error changing unavailability', e);
      } finally {
        loading.value = false;
      }
    }

    return {
      open,
      loading,
      dates,
      vacationMode,
      todayDate,
      addDateLine,
      removeDateLine,
      close,
      submit,
      errors,
    };
  },
};
</script>
