<template>
  <ModalWrapper
    v-if="open"
    title="Vos paramètres"
    modal-class="modal-modif-dispo"
    @on-close-modal="$emit('close')"
  >
    <form class="sas-snp-user-data-form" @change="check">
      <div v-if="popInIsLoading" class="simple-loader-backdrop">
        <div class="pop-in-loading-wrapper">
          <h2>Enregistrement en cours</h2>
          <RingLoader />
        </div>
      </div>

      <div class="participation-sas">
        <h2>Créneaux en sus de mes disponibilités</h2>
        <div class="form-item-participation-sas">
          <input
            type="checkbox"
            id="edit_sas_participation"
            name="sas_participation"
            :value="form.sasParticipation"
            v-model="form.sasParticipation"
            class="form-checkbox"
            @change="handleChangeForSasParticipation()"
          />
          <label for="edit_sas_participation">
            Je participe au SAS
          </label>
        </div>

        <div v-if="form.sasParticipation" class="participation-sas-select">
          <select
            id="edit_sas_participation_via"
            name="sas_participation_via"
            v-model.number="form.sasParticipationVia"
            :value="form.sasParticipationVia"
            @change="handleChangeForParticipationVia()"
          >
            <option value="">Sélectionner</option>
            <option value="1">à titre individuel</option>
            <option value="2">via ma CPTS</option>
            <option value="3">via ma MSP</option>
            <option value="4">via mon association SOS Médecins</option>
          </select>
        </div>
      </div>

      <!-- via ma CPTS/MSP/SOS -->
      <div class="cpts-container">
        <DashboardSettingsAutocomplete
          v-if="form.sasParticipationVia && form.sasParticipationVia && form.sasParticipationVia !== 1"
          :sasParticipationVia="form.sasParticipationVia"
          @get-autocomplete-val="getAutocompleteValue"
          :autocompleteValue="form.autocompleteValue"
          :label="structureAutocompleteLabel"
          :showStructureLoader="showStructureLoader"
        />
        <div class="cpts-places">
          <fieldset v-if="form.sasParticipationVia === 2" class="fieldgroup">
            <legend>
              <span>
                Quel(s) lieu(x) d'activité est/sont rattaché(s) à votre CPTS ? <span aria-hidden="true">*</span><span class="sr-only">Champ obligatoire</span>
              </span>
            </legend>

            <DashboardSettingsAddresses
              :addresses="addresses"
              :checkedAddresses="form.cptsLocation"
              @get-addresses-val="getAddressesValue"
            />
          </fieldset>
        </div>
      </div>
      <!-- END via ma CPTS/MSP/SOS -->

      <!-- à titre individuel && CPTS && MSP -->
      <div v-if="form.sasParticipationVia > 0 && form.sasParticipationVia !== 4" class="software-container">
        <fieldset class="fieldgroup">
          <legend>
            <span>
              Utilisez-vous une solution de prise de rendez-vous ? <span aria-hidden="true">*</span><span class="sr-only">Champ obligatoire</span>
            </span>
          </legend>

          <div class="d-flex software-choice">
            <div>
              <input
                type="radio"
                id="has_not_software"
                name="has_software"
                :value="false"
                v-model="form.hasSoftware"
                @change="resetValueIfSoftware"
                class="form-radio"
              />
              <label for="has_not_software">Non</label>
            </div>

            <div>
              <input
                type="radio"
                id="has_software"
                name="has_software"
                :value="true"
                v-model="form.hasSoftware"
                @change="resetValueIfSoftware"
                class="form-radio"
              />
              <label for="has_software"
              >Oui, je déclare sur l'honneur utiliser un (ou plusieurs)
                logiciel de prise de rendez-vous interfacé avec la
                plateforme</label
              >
            </div>
          </div>
        </fieldset>
        <div v-if="form.hasSoftware === false">
          <input
            type="checkbox"
            id="edit-editor-disabled-hours-available"
            name="hours_available"
            v-model="form.hoursAvailableSoftware"
            class="form-checkbox"
          />
          <label for="edit-editor-disabled-hours-available"
          >Je déclare sur l'honneur mettre en visibilité à minima 2 heures de
            disponibilité par semaine sur mon agenda de la plateforme numérique
            SAS</label
          >
        </div>
        <div v-else-if="form.hasSoftware && editorList.length">
          <RingLoader v-if="editorListIsLoading" />

          <template v-else-if="!form.editorDisabled">
            <label class="editor_name" for="editor_name">
              Indiquez votre/vos logiciel(s) de gestion de rendez-vous <span aria-hidden="true">*</span><span class="sr-only">Champ obligatoire</span>
            </label>

            <DashboardSettingsEditorSelector
              :editorsList="editorList"
              :defaultEditors="defaultEditor"
              @update-editors-list="updateEditorsList"
            />
            <ul v-if="form.editorName.length" class="tag-list">
              <li v-for="(el, idx) in form.editorName" :key="`editor-${idx}`">
                <button type="button" @click.prevent="deleteEditor(el)">
                  {{ el.corporateName }}
                </button>
              </li>
            </ul>
          </template>
        </div>
      </div>
      <!-- END à titre individuel && CPTS -->

      <template v-if="form.sasParticipationVia !== 4">
        <div class="editor-disabled">
          <h2>Remontée des créneaux de ma solution de prise de rendez-vous</h2>
          <div class="input-group">
            <input
              type="checkbox"
              id="edit_editor_disable"
              name="editor_disable"
              v-model="form.editorDisabled"
              :disabled="disabledEditEditorInput"
              @change="form.hoursAvailableEditor = false"
              class="form-checkbox"
            />
            <label for="edit_editor_disable"
            >Je refuse d'afficher mes créneaux « grand public » et/ou «
              professionnel » disponibles dans la plateforme numérique
              SAS</label
            >
          </div>
        </div>

        <div v-if="form.editorDisabled && form.sasParticipation && !disabledEditEditorInput" class="input-group editor-disabled-hour">
          <input
            type="checkbox"
            id="edit-editor-disabled-hours-available"
            name="hours_available"
            v-model="form.hoursAvailableEditor"
            class="form-checkbox"
          />
          <label for="edit-editor-disabled-hours-available"
          >Je déclare sur l'honneur mettre en visibilité à minima 2 heures de
            disponibilité par semaine sur mon agenda de la plateforme numérique
            SAS</label
          >
        </div>

        <div v-if="form.editorDisabled && !form.sasParticipation" class="edit-sas-participation-alert">
          <p>
            Si vous renseignez directement sur l'agenda de la plateforme
            numérique 2 heures par semaine, sans avoir coché la section « Je participe au SAS »,
            <strong>alors vous n'êtes pas éligible à la rémunération SAS prévue par
              l'avenant n°9.</strong>
          </p>
        </div>
      </template>

      <h2 class="reorientation-title">Forfait de réorientation</h2>
      <div>
        <input
          type="checkbox"
          id="edit-forfait-reo-enabled"
          name="forfait-reo"
          v-model="form.forfaitReo"
          class="form-checkbox"
        />
        <label for="edit-forfait-reo-enabled"
        >Je participe au forfait de réorientation des urgences</label
        >
      </div>
      <div class="wrapper-btn-actions">
        <button
          type="button"
          @click="$emit('close')"
          class="btn-highlight-outline btn-cancel"
        >
          Annuler
        </button>
        <button
          type="button"
          :disabled="!isAvailableToSave"
          @click="save"
          class="btn-hightlight-outline form-submit"
        >
          Enregistrer
        </button>
      </div>
    </form>
  </ModalWrapper>
</template>

<script>
import {
 ref, onMounted, computed, watch,
} from 'vue';
import _isEmpty from 'lodash.isempty';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import { DashboardService, SettingService } from '@/services';
import { EditorListModel } from '@/models';
import { cookie } from '@/helpers';
import { useUserDashboard } from '@/stores';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';
import DashboardSettingsAutocomplete from './DashboardSettingsAutocomplete.component.vue';
import DashboardSettingsAddresses from './DashboardSettingsAddresses.component.vue';
import DashboardSettingsEditorSelector from './DashboardSettingsEditorSelector.component.vue';

export default {
  components: {
    ModalWrapper,
    DashboardSettingsAutocomplete,
    DashboardSettingsAddresses,
    DashboardSettingsEditorSelector,
    RingLoader,
  },
  props: {
    open: {
      type: Boolean,
      default: true,
    },
    settings: {
      type: Object,
      default: () => ({}),
    },
    rppsAdeli: {
      type: String,
      default: '',
    },
  },
  emits: ['close', 'refresh'],
  setup(props, { emit }) {
    const form = ref({
      userId: null,
      sasParticipation: false,
      sasParticipationVia: null,
      hasSoftware: null,
      editorDisabled: false,
      editorName: [],
      hoursAvailableSoftware: false,
      hoursAvailableEditor: false,
      forfaitReo: false,
      cptsLocation: [],
      autocompleteValue: '',
    });

    // to chose post of put method to save pop-in for sas-api
    const isFirstSave = computed(() => !!_isEmpty(props.settings));

    // on change of sas participation & participation via feature

    function handleChangeForSasParticipation() {
      form.value.sasParticipationVia = '';
      form.value.hasSoftware = null;
    }

    function handleChangeForParticipationVia() {
      form.value.autocompleteValue = '';
      form.value.hoursAvailableSoftware = false;
    }

    // adresses feature
    const userDashboardStore = useUserDashboard();
    const addresses = computed(() => userDashboardStore.userAddresses);

    function getAddressesValue(val) {
      form.value.cptsLocation = val.rppsRang;
      check();
    }

    // editor features
    const editorList = ref({});

    const editorListIsLoading = ref(false);

    /**
     * check if agregator is in cookie storage before call all aggregator's endpoints
     */
    async function callAggregatorEndpoints() {
      editorListIsLoading.value = true;
      if (!cookie.getCookie('sas_aggregator_token')) {
        await SettingService.getAggregatorToken();
      }
      await getEditorList();
      const userEditor = await fetchEditorList();
      form.value.editorName = userEditor;
      editorListIsLoading.value = false;
    }

    /**
     * get Editor list from API
     */
    async function getEditorList() {
      const res = await DashboardService.getEditorList();
      editorList.value = new EditorListModel(res).getEditorNameList();
    }

    /**
     * add selected editor in the list and disable option
     * @param {*} editor
     */
    function addToEditorNameList(editor) {
      form.value.editorName.push(editor);
      if (checkSoftwareConditions()) {
        isAvailableToSave.value = true;
      }
    }

     const defaultEditor = computed(() => form.value.editorName.map((editor) => editor.id));

    /**
     * delete selected editor from the list of buttons and remove disable
     * @param {*} editor
     */
    function deleteEditor(editor) {
      const target = form.value.editorName.indexOf(editor);
      form.value.editorName.splice(target, 1);
      check();
      if (checkSoftwareConditions()) {
        isAvailableToSave.value = true;
      }
    }

    const autocompleteCheck = ref({});
    const structureAutocompleteLabel = ref('');

    /**
     * get autocomplete value from autocomplete component
     */
    function getAutocompleteValue(el) {
      form.value.autocompleteValue = el.autocompleteValue;
      autocompleteCheck.value = el.autocompleteCheck;
      check();
    }

    const disabledEditEditorInput = ref(false);

    /**
     * fetch data from agreg API
     */
    async function fetchEditorList() {
      const newArr = [];
      const res = await DashboardService.fetchUserEditorList(form.value.userId);
      const listEditors = Object.values(res);

      if (listEditors.length) {
        listEditors.forEach((el) => newArr.push(el.editor));
      }
      return newArr;
    }

    /**
     * Updated editor's values in the form value
     * @param {} el
     */
    function updateEditorsList(el) {
      resetValueIfSoftware();
      return !el.isChecked ? addToEditorNameList(el.selectedEditors) : deleteEditor(el.selectedEditors);
    }

    // enable save button feature
    const isAvailableToSave = ref(false);

    // handle value when has software input change feature

    /**
     * handle change of hasSoftware input
     */
    function resetValueIfSoftware() {
      if (form.value.hasSoftware) {
        form.value.editorDisabled = false;
      } else {
        form.value.editorDisabled = true;
        form.value.hoursAvailableEditor = true;
        disabledEditEditorInput.value = true;
      }
      form.value.hoursAvailableSoftware = false;
    }

    /**
     * check all rules about software info to turn on save button
     */
    function checkSoftwareConditions() {
      if (!form.value.hasSoftware) {
        disabledEditEditorInput.value = true;
        form.value.editorDisabled = true;
        return form.value.hoursAvailableSoftware;
      }
      if (!form.value.sasParticipation) {
        form.value.editorDisabled = false;
        form.value.hoursAvailableEditor = false;
      }
      if (form.value.editorDisabled) {
        return form.value.hoursAvailableEditor;
      }
      return form.value.hasSoftware && form.value.editorName.length > 0;
    }

    // loader during fetching structure autocomplete value
    const showStructureLoader = ref(false);

    // check if editor autocomplete get a value & value if from API
    const isValidEditorAutocompleteConditions = computed(() => form.value.autocompleteValue && autocompleteCheck.value.check);

    /**
     * handle values and disable/enable save button
     */
    function check() {
      isAvailableToSave.value = false;
      disabledEditEditorInput.value = false;

      if (!form.value.sasParticipation) {
        form.value.hoursAvailableEditor = form.value.editorDisabled;
        form.value.sasParticipationVia = null;
        form.value.hasSoftware = false;
        form.value.hoursAvailableSoftware = true;
        isAvailableToSave.value = true;
      } else if (form.value.sasParticipation) {
        // à titre individuel
        if (form.value.sasParticipationVia === 1) {
          isAvailableToSave.value = checkSoftwareConditions();
          // cpts
        } else if (form.value.sasParticipationVia === 2) {
          disabledEditEditorInput.value = !form.value.hasSoftware;
          if (
            form.value.cptsLocation.length
            && isValidEditorAutocompleteConditions.value
            && checkSoftwareConditions()
          ) {
            isAvailableToSave.value = true;
          }
          // msp
        } else if (form.value.sasParticipationVia === 3) {
          disabledEditEditorInput.value = !form.value.hasSoftware;
          if (
            isValidEditorAutocompleteConditions.value
            && checkSoftwareConditions()
          ) {
            isAvailableToSave.value = true;
          }
          // sos médecins
        } else if (
            form.value.sasParticipationVia === 4
            && isValidEditorAutocompleteConditions.value
          ) {
          form.value.editorDisabled = true;
          isAvailableToSave.value = true;
        }
      }
    }

    onMounted(async () => {
      // fetch data from APIs feature
      // to get defaults values

      /* eslint-disable camelcase */
      const {
        has_software,
        participation_sas,
        participation_sas_via,
        forfait_reo_enabled,
        editor_disabled,
        hasSoftware,
        cpts_locations,
        siret,
        structure_finess,
      } = props.settings;

      form.value.hasSoftware = has_software;
      form.value.sasParticipation = participation_sas;
      form.value.sasParticipationVia = participation_sas_via ?? null;
      form.value.forfaitReo = forfait_reo_enabled;
      form.value.editorDisabled = editor_disabled;
      form.value.hoursAvailableSoftware = !hasSoftware;
      form.value.hoursAvailableEditor = editor_disabled;
      form.value.userId = props.rppsAdeli;

      await callAggregatorEndpoints();

      if (form.value.sasParticipation) {
        // for participation via CPTS
        if (form.value.sasParticipationVia === 2) {
          form.value.cptsLocation = cpts_locations;
        }
        // for fetch structure label if participation via CPTS, MSP or SOS médecins
        if (form.value.sasParticipationVia > 1) {
          const getLabelFromSasParticipationId = (id) => {
            switch (id) {
              case 2: return 'cpts';
              case 3: return 'msp';
              case 4: return 'sos';
              default: return '';
            }
          };
          showStructureLoader.value = true;
          const idType = getLabelFromSasParticipationId(form.value.sasParticipationVia);
          const isSOSMedecins = form.value.sasParticipationVia === 4;
          const structureId = isSOSMedecins ? siret : structure_finess;
          const res = await DashboardService.fetchAutocompleteLabel(idType, structureId);
          structureAutocompleteLabel.value = !_isEmpty(res) ? `${res.title} (${res.id})` : '';
          showStructureLoader.value = false;
        }
      }
      /* eslint-enable camelcase */

      check();
    });

    watch(() => form.value.editorName, () => {
      check();
    });

    // saving feature

    const popInIsLoading = ref(false);

    /**
     * save and close modal
     */
    async function save() {
      if (isAvailableToSave.value) {
        popInIsLoading.value = true;
        isAvailableToSave.value = false;

        const sendSettings = await DashboardService.updateDashboardUserSettings(getSasPayload(), form.value.userId, isFirstSave.value);

        await DashboardService.putUserEditorList(getAgregPayload(), form.value.userId);
        // success for sas request
        if (!_isEmpty(sendSettings)) {
          emit('refresh');
        }
        emit('close');
        popInIsLoading.value = false;
      }
    }

    /**
     * get value between parenthese to fetch only id
     * @param {*} val
     */
    function getBetweenParentheseValue(val) {
      const regExp = /\(([^)]+)\)/;
      return regExp.exec(val)[1];
    }

    /**
     * build the payload for SAS-API
     */
    function getSasPayload() {
      // handle hour availables variable for payload
      const hourAvailable = (!form.value.hasSoftware && form.value.hoursAvailableSoftware) || (form.value.editorDisabled && form.value.hoursAvailableEditor) || (!form.value.sasParticipation && form.value.hoursAvailableSoftware);

      const isSosMedecins = form.value.sasParticipationVia === 4;

      const sasPayload = {
        editor_disabled: form.value.editorDisabled,
        forfait_reo_enabled: form.value.forfaitReo || false,
        participation_sas: form.value.sasParticipation,
        participation_sas_via: form.value.sasParticipationVia || false,
        has_software: form.value.hasSoftware,
        hours_available: isSosMedecins || hourAvailable,
        structure_finess: form.value.sasParticipation && (form.value.sasParticipationVia === 2 || form.value.sasParticipationVia === 3) ? getBetweenParentheseValue(form.value.autocompleteValue) : null,
        cpts_locations: form.value.sasParticipation && form.value.sasParticipationVia === 2 ? form.value.cptsLocation : [],
        siret: form.value.sasParticipation && form.value.sasParticipationVia === 4 ? getBetweenParentheseValue(form.value.autocompleteValue) : false,
      };

      // added this key/value for post request only & do not keep the first character of user_id
      if (isFirstSave.value) {
        Object.assign(sasPayload, {
          user_id: form.value.userId.substring(1),
        });
      }

      return sasPayload;
    }

    /**
     * build the payload for agregator's API
     */
    function getAgregPayload() {
      const ids = [];
      // if there are not sofware OR sasParticipation is false, update with an empty array
      if (form.value.hasSoftware) {
        form.value.editorName.forEach((el) => {
          ids.push(el.id);
        });
      }
      if (!form.value.sasParticipation) {
        form.value.editorName = form.value.editorName.splice(0, form.value.editorName.length);
      }

      return {
        editorIds: ids,
      };
    }

    return {
      form,
      addresses,
      addToEditorNameList,
      editorList,
      deleteEditor,
      isAvailableToSave,
      resetValueIfSoftware,
      check,
      disabledEditEditorInput,
      autocompleteCheck,
      getAutocompleteValue,
      getAddressesValue,
      updateEditorsList,
      defaultEditor,
      save,
      editorListIsLoading,
      popInIsLoading,
      structureAutocompleteLabel,
      showStructureLoader,
      handleChangeForParticipationVia,
      handleChangeForSasParticipation,
    };
  },
};
</script>
