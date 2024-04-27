<template>

  <slot :openModal="openModal" />
  <ModalWrapper v-if="open" @on-close-modal="close" :title="config.group1.title" modal-class="modal-modif-dispo">
    <div>
      <div class="form-errors errors" v-if="errors.length">
        <ul class="resetul">
          <li v-for="(error, i) in errors" :key="i">
            {{error}}
          </li>
        </ul>
      </div>
      <p>{{config.group1.subtitle}}</p>
      <form @submit.prevent="checkBeforeSubmit">
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

        <fieldset class="fieldset-date">
          <legend class="sr-only">Paramétrage de la date et heure</legend>
          <div class="form-item form-type-date">
            <label for="edit-date">
              {{config.group2.date}}
              <span class="form-required" title="Ce champ est requis.">*</span> :
            </label>
            <input id="edit-date" type="date" v-model="form.date" disabled>
          </div>

          <div class="form-item form-type-select js-form-type-select">
            <label for="from">
              {{config.group2.from}}
              <span class="form-required" title="Ce champ est requis.">*</span> :
            </label>

            <select name="" id="from" v-model="form.from">
              <option v-for="(option, index) in hoursOptions" :key="index" :value="option.value">{{option.label}}</option>
            </select>
          </div>

          <div class="form-item form-type-select js-form-type-select">
            <label for="to">
              {{config.group2.to}}
              <span class="form-required" title="Ce champ est requis.">*</span> :
            </label>

            <select name="" id="to" v-model="form.to">
              <option v-for="(option, index) in hoursOptions" :key="index" :value="option.value">{{option.label}}</option>
            </select>
          </div>
        </fieldset>

        <fieldset>
          <legend class="sr-only">Choix du type de rendez-vous</legend>
          <div class="wrapper-edit-types">
            <div class="form-item radio-standard">
              <input
                type="radio"
                name="choice-visibility"
                id="visibility"
                :checked="form.typeOfAppointment === 'single'"
                @click=" form.typeOfAppointment = 'single'"
              >
              <label for="visibility">
                <span>
                  {{config.group3.consultation}}
                  <span class="example">(Ex. : 10h00-10h15, 10h15-10h30, ...)</span>
                </span>
              </label>
            </div>

            <div class="form-item radio-standard">
              <input
                type="radio"
                name="choice-visibility"
                id="nb_patient"
                :checked="form.typeOfAppointment === 'multiple'"
                @click="form.typeOfAppointment = 'multiple'"
              >
              <label for="nb_patient">
                <span class="nb-patient">
                  {{nbPatientSentence.partOne}}
                  <input
                    type="number"
                    v-model="form.maxPatients"
                    :disabled="form.typeOfAppointment !== 'multiple'"
                    min="1"
                  >
                  {{nbPatientSentence.partTwo}}
                </span>
              </label>
            </div>
          </div>
          <p
            v-if="form.typeOfAppointment === 'single' && startAndEndHourAreTooFarApart"
            class="txt-red"
          >Pour les disponibilités supérieures à 1h, la création d'une plage horaire est recommandée</p>
        </fieldset>

        <fieldset class="consultation">
          <legend class="txt-bold-big">Type de consultation : </legend>
          <div class="fieldset-wrapper">
            <div class="form-wrapper">
              <div class="form-item form-type-checkbox">
                <input
                  type="checkbox"
                  v-model="form.modalities"
                  :value="config.group4.office"
                  id="consultation_modality_physical"
                >
                <label for="consultation_modality_physical">
                  <span class="tag-letter" aria-hidden="true">{{config.group4.initialOffice}}</span>
                  {{config.group4.office}}
                </label>
              </div>
            </div>

            <div class="form-wrapper">
              <div class="form-item form-type-checkbox">
                <input
                  type="checkbox"
                  v-model="form.modalities"
                  :value="config.group4.teleconsultation"
                  id="consultation_modality_teleconsultation"
                >
                <label for="consultation_modality_teleconsultation">
                  <span class="tag-letter" aria-hidden="true">{{config.group4.initialTeleconsultation}}</span>
                  {{config.group4.teleconsultation}}
                </label>
              </div>
            </div>

            <div class="form-wrapper">
              <div class="form-item form-type-checkbox">
                <input
                  type="checkbox"
                  v-model="form.modalities"
                  :value="config.group4.home"
                  id="consultation_modality_home"
                >
                <label for="consultation_modality_home">
                  <span class="tag-letter" aria-hidden="true">{{config.group4.initialHome}}</span>
                  {{config.group4.home}}
                </label>
              </div>
            </div>
          </div>
        </fieldset>

        <fieldset class="consultation">
          <legend class="txt-bold-big">Appliquer cette modification :</legend>
          <div class="fieldset-wrapper">
            <div class="form-item radio-standard">
              <input
                type="radio"
                id="modification-scope-recurring"
                name="modification-scope-radio"
                :disabled="currentSlot.type === 'dated'"
                :checked="form.affectAllOccurence === true"
                @click="form.affectAllOccurence = true"
              >
              <label for="modification-scope-recurring">
                à toutes les semaines
              </label>
            </div>

            <div class="form-item radio-standard">
              <input
                type="radio"
                id="modification-scope-dated"
                name="modification-scope-radio"
                :checked="form.affectAllOccurence === false"
                @click="form.affectAllOccurence = false"
              >
              <label for="modification-scope-dated">
                à cette occurence uniquemement: {{formattedDate}}
              </label>
            </div>
          </div>
        </fieldset>

        <div class="wrapper-btn-actions">
          <button type="button" class="btn-highlight-outline btn-cancel js-btn-cancel" @click="close">
            Annuler
          </button>
          <button type="submit" class="btn-hightlight-outline form-submit" :disabled="loading">
            Enregistrer
          </button>
        </div>
      </form>
    </div>
  </ModalWrapper>
</template>

<script>
import {
 ref, reactive, computed, watch,
} from 'vue';
import _isEqual from 'lodash.isequal';
import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import CalendarService from '@/services/calendar.service';
import {
 convertToSeconds, generateHoursOptions, convertStringHourToNbSeconds, formatTimeZoneToHour,
} from '@/helpers';
import { SnpPopinConfigModel, SlotModel } from '@/models';
import SNP_CONST from '@/const/snp.const';

dayjs.extend(isSameOrBefore);
dayjs.extend(timezone);
dayjs.extend(utc);

export default {
  components: { ModalWrapper },
  emits: ['submited'],
  props: {
    config: {
      type: SnpPopinConfigModel,
      default: () => new SnpPopinConfigModel(),
    },
    currentSlot: {
      type: SlotModel,
      default: () => new SlotModel(),
    },
    date: {
      type: String,
      required: true,
    },
  },
  setup(props, { emit }) {
    const open = ref(false);
    const loading = ref(false);
    const hoursOptions = generateHoursOptions();
    const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    const currentTz = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';
    const defaultForm = computed(() => {
      const dateSlot = props.currentSlot?.type === 'recurring' ? props.currentSlot?.real_date : props.currentSlot?.date;
      const startDateString = dateSlot ? dateSlot.split('T') : [];
      const slotTimeZone = props.currentSlot?.timeZone ? props.currentSlot?.timeZone : currentTz;

      const startDateLabel = startDateString.length > 0 ? startDateString[0] : dayjs().utc(true);
      const endDateLabel = startDateString.length > 0 ? startDateString[0] : dayjs().utc(true);

      const slotStartHour = props.currentSlot?.startHours.slice(0, -2);
      const slotStartMinute = props.currentSlot?.startHours.slice(-2);
      const slotEndHour = props.currentSlot?.endHours.slice(0, -2);
      const slotEndMinute = props.currentSlot?.endHours.slice(-2);

      let startDate = dayjs(`${startDateLabel} ${slotStartHour}:${slotStartMinute}`).utc(true);
      startDate = startDate.tz(slotTimeZone);
      let endDate = dayjs(`${endDateLabel} ${slotEndHour}:${slotEndMinute}`).utc(true);
      endDate = endDate.tz(slotTimeZone);

      if (
        slotTimeZone === 'Europe/Paris'
        && startDate.utcOffset() === 120
      ) {
        startDate = dayjs(`${startDateLabel} ${slotStartHour}:${slotStartMinute}:00+01:00`).utc(true);
        endDate = dayjs(`${endDateLabel} ${slotEndHour}:${slotEndMinute}:00+01:00`).utc(true);
      }

      return {
        date: props.date,
        from: `${startDate.utc(true).format('HH')}h${startDate.utc(true).format('mm')}`,
        to: `${endDate.utc(true).format('HH')}h${endDate.utc(true).format('mm')}`,
        typeOfAppointment: props.currentSlot.max_patients > -1 ? 'multiple' : 'single', // null || 'single' || 'multiple
        maxPatients: props.currentSlot.max_patients > -1 ? props.currentSlot.max_patients : 0,
        modalities: props.currentSlot.getModalities().map((key) => props.config.group4[SNP_CONST.mapPropertiesFrontBack[key]]),
        affectAllOccurence: false,
      };
    });

    const startAndEndHourAreTooFarApart = computed(() => {
      const recommandeMaxSecondIntervalBetweenStartAndEnd = 3600;
      const startSec = convertStringHourToNbSeconds(form.from);
      const endSec = convertStringHourToNbSeconds(form.to);

      return endSec - startSec > recommandeMaxSecondIntervalBetweenStartAndEnd;
    });

    const form = reactive({ ...defaultForm.value });
    // Rebuild Form object if defaultForm has changed.
    watch(defaultForm, (newDefaultForm, oldDefaultForm) => {
      const objectsAreDifferent = !_isEqual(newDefaultForm, oldDefaultForm);
      if (objectsAreDifferent) {
        Object.keys(form).forEach((key) => {
          form[key] = newDefaultForm[key];
        });
      }
    });
    const formattedDate = computed(() => dayjs(form.date).format('DD/MM/YYYY'));
    const errors = ref([]);
    const nbPatientSentence = computed(() => {
        const [partOne, partTwo] = props.config.group3.meet.split('[snp:popin_case]').map((e) => e.trim()) || ['', ''];
        return {
         partOne,
         partTwo,
        };
    });

    function openModal() {
      open.value = true;
    }

    function formValidator(key) {
      // Form Rules.
      // "date" should not be null
      // "to" and "from" should not be null
      // "to" should not be before "from"
      // "typeOfAppointment" should not be null
      // if "typeOfAppointment" multiple is selected, "maxPatients" should not be null
      // "modalities" should not be empty array

      switch (key) {
        case 'date':
          return form.date !== null || 'Une date valide est obligatoire.';
        case 'from':
          if (form.from === null) {
            return 'L\'heure de début doit être renseigné.';
          }
          return dayjs().isSameOrBefore(
            dayjs(form.date, 'YYYY/MM/DD').add(Number(form.from.slice(0, 2)), 'hour').add(Number(form.from.slice(3)), 'minute'),
          ) || 'Vous ne pouvez créer un créneau dans le passé.';
        case 'to': {
          if (form.to === null) {
            return 'L\'heure de fin doit être renseigné';
          }
          const isEndHoursAfterStart = !form.from || convertToSeconds(form.to.slice(0, 2), form.to.slice(3)) > convertToSeconds(form.from.slice(0, 2), form.from.slice(3));
          return isEndHoursAfterStart || 'L\'heure de début doit être antérieure à l\'heure de fin.';
        }
        case 'typeOfAppointment':
          return form.typeOfAppointment !== null || 'Veuillez sélectionner un type (créneau ou plage horaire).';
        case 'maxPatients':
          if (form.typeOfAppointment === 'multiple') {
            return form.maxPatients !== null && form.maxPatients > 0 ? true : 'Nombre de patients incorrect.';
          }
          return true;
        case 'modalities':
          return form.modalities.length > 0 || 'Veuillez sélectionner au moins un type de consultation.';
        default:
      }
      return true;
    }

    async function submit() {
       loading.value = true;
      try {
        const userTZ = window?.API?.['time-slot-schedule']?.timezone || 'Europe/Paris';
        // europe/paris is forced to +01:00 for SAS api recurring data
        const tzHour = (userTZ === 'Europe/Paris') ? '+01:00' : formatTimeZoneToHour(userTZ);
        const startHours = Number(form.from.slice(0, 2));
        const startMinutes = Number(form.from.slice(3));
        const formApi = {
          schedule: {
            id: CalendarService.getScheduleId(),
            timezone: userTZ,
          },
          slot: {
            id: props.currentSlot.id,
          },
          date: dayjs(form.date, 'YYYY/MM/DD')
            .add(startHours, 'hour')
            .add(startMinutes, 'minute')
            .format('YYYY-MM-DDTHH:mm:ss') + tzHour,
          start_hours: form.from.replaceAll('h', ''),
          end_hours: form.to.replaceAll('h', ''),
          type: props.currentSlot.type,
          modalities: form.modalities.map((modality) => Object.keys(SNP_CONST.mapPropertiesFrontBack).find((k) => props.config.group4[SNP_CONST.mapPropertiesFrontBack[k]] === modality)),
          max_patients: form.typeOfAppointment === 'multiple' ? form.maxPatients : -1,
          item_in_recurrence: props.currentSlot.type === 'dated' ? false : !form.affectAllOccurence,
          day: props.currentSlot.day,
        };
        await CalendarService.putSlot(formApi, CalendarService.getNodeId());
        close();
        emit('submited');
      } catch (e) {
        console.error('Error formatting form into API format', e);
      } finally {
        loading.value = false;
      }
    }

    function checkBeforeSubmit(event) {
      event.preventDefault();
      const validatorCheckers = Object.keys(form).map((key) => formValidator(key));
      if (validatorCheckers.every((e) => e === true)) {
       submit();
      }
      errors.value = validatorCheckers.filter((e) => e !== true);
    }

    function resetForm() {
      Object.keys(form).forEach((key) => {
        form[key] = defaultForm.value[key];
      });
      // reset errors;
      errors.value.length = 0;
    }

    function close() {
      resetForm();
      open.value = false;
    }

    return {
      open,
      loading,
      formattedDate,
      openModal,
      hoursOptions,
      days,
      form,
      nbPatientSentence,
      errors,
      checkBeforeSubmit,
      close,
      startAndEndHourAreTooFarApart,
    };
  },
};
</script>
