<template>
  <ModalWrapper
    v-if="openModal"
    :title="modalTitle"
    modal-class="modal-modif-dispo"
    @on-close-modal="$emit('close')"
  >
    <Spinner v-if="isLoading">
      Traitement de la requête
    </Spinner>

    <component
      v-else
      :is="componentName"
      v-bind="componentProps"
      @registration-updated="registrationUpdated"
      @confirmation-save="confirmationSave"
      @confirmation-update="confirmationUpdate"
      @notification-confirmed="notificationConfirmed"
      @close="$emit('close')"
    />
  </ModalWrapper>
</template>

<script>
import { ref, computed } from 'vue';

import dayjs from 'dayjs';
import timezone from 'dayjs/plugin/timezone';
import Spinner from '@/components/sharedComponents/loader/Spinner.component.vue';
import ModalWrapper from '@/components/sharedComponents/modals/ModalWrapper.component.vue';
import RegistrationTab from '@/components/searchComponents/orientationModal/orientationTab/RegistrationTab.component.vue';
import ConfirmationTab from '@/components/searchComponents/orientationModal/orientationTab/ConfirmationTab.component.vue';
import NotificationTab from '@/components/searchComponents/orientationModal/orientationTab/NotificationTab.component.vue';

import { useSearchData, useUserData } from '@/stores';
import { useOrientation, useSearchHelper } from '@/composables';

dayjs.extend(timezone);

const title = {
  surnumeraire: 'ADRESSER UN PATIENT HORS CRÉNEAUX',
  creneaux: 'ADRESSER UN PATIENT',
  plage: 'ADRESSER UN PATIENT',
};

export default {
  name: 'OrientationModal',
  components: {
    Spinner,
    ModalWrapper,
    RegistrationTab,
    ConfirmationTab,
    NotificationTab,
  },
  props: {
    cardData: {
      type: Object,
      default: () => ({}),
    },
    calendarSlotData: {
      type: Object,
      default: () => ({}),
    },
    type: {
      type: String,
      default: '',
    },
    open: {
      type: Boolean,
      default: true,
    },
  },
  emits: ['close', 'refreshSlots'],
  setup(props, { emit }) {
    const searchDataStore = useSearchData();
    const userDataStore = useUserData();

    const { getDetailsPageUrl } = useSearchHelper();
    const {
      checkNullValue,
      formatDateTime,
      setOrientationRegistration,
    } = useOrientation();

    const isLoading = ref(false);
    const isSasOverbookingFilterChecked = computed(() => searchDataStore.customFilters['bs_sas_overbooking'] !== undefined);
    const openModal = ref(props.open);
    const componentName = ref(RegistrationTab.name);
    const payload = ref({});
    const modalTitle = computed(() => title[props.type]);
    const response = ref({});
    const componentProps = ref(createProps());

    const registrationUpdated = (date) => {
      componentName.value = ConfirmationTab.name;
      componentProps.value = createProps(date || props.calendarSlotData.real_date);
    };

    function getOrientationPayload() {
      const user = userDataStore.currentUser;
      const { type, cardData } = props;
      const slotData = props.calendarSlotData;
      const slotDate = componentProps.value.date;

      // SAS-1944 : OSNP && classic => 1, OSNP && surnuméraire => 2, IOA => 3
      let orientationType = null;
      if (user.isRegulateurOSNP) {
        if (
          type === 'creneaux'
          || type === 'plage'
        ) {
          orientationType = 1;
        } else {
          orientationType = 2;
        }
      } else if (user.isRegulateurIOA) {
        orientationType = 3;
      }

      payload.value = {
        regulator: {
          email: user.email || '',
          lastname: user.lastname || '',
          firstname: user.firstname || '',
          // SAS-1857 : Id territory id from SF instead of drupal
          territory: (user.territoryApiId || []).map((id) => ({ id: parseInt(id, 10) })),
          county: user.county || '',
          county_number: user.countyNumber || '',
        },
        orientation_date: formatDateTime(),
        orientation_type: orientationType,
        orientation_status: 0,
        slot_date: formatDateTime(slotDate),
        slot: type === 'surnumeraire' ? null : { id: slotData.id },
        slot_modality: slotData.modalities || [],
      };

      payload.value['orientation_timezone'] = dayjs.tz.guess();

      if (slotData.timezone) {
        payload.value['slot_timezone'] = slotData.timezone;
      }

      // recipient : CPTS & overbooking specific case
      if (type === 'surnumeraire' && cardData.ss_sas_cpts_finess) {
        payload.value.recipient = {
          type: 2,
          structure_finess: cardData.ss_sas_cpts_finess,
        };
      } else {
        // All other case :
        payload.value.recipient = {
          type: cardData.ss_field_identifiant_rpps || cardData.ss_field_personne_adeli_num ? 1 : 2,
          effector_rpps: checkNullValue(cardData.ss_field_identifiant_rpps),
          effector_adeli: checkNullValue(cardData.ss_field_personne_adeli_num),
          // eslint-disable-next-line no-nested-ternary
          structure_finess: cardData.ss_field_identifiant_finess
            ? cardData.ss_field_identifiant_finess
            : (cardData.ss_field_identifiant_str_finess
              ? cardData.ss_field_identifiant_str_finess
              : null),
          effector_speciality: cardData.tm_X3b_und_spec_tags ? cardData.tm_X3b_und_spec_tags[0] : null,
          effector_territory: (user.territoryApiId || []).map((id) => ({ id: parseInt(id, 10) })),
        };
      }

      // Then add common keys/values for recipient's payload
      payload.value.recipient.name = cardData.tm_X3b_und_title[0];
      payload.value.recipient.address = checkNullValue(cardData.ss_field_address);
      payload.value.recipient.structure_siret = checkNullValue(cardData.ss_field_identif_siret);
      payload.value.recipient.county = cardData.tm_X3b_und_field_department && cardData.tm_X3b_und_field_department.length ? cardData.tm_X3b_und_field_department[0] : '';
      payload.value.recipient.county_number = cardData.ss_field_department_code;
      payload.value.recipient.structure_type = cardData.tm_X3b_und_establishment_type_names && cardData.tm_X3b_und_establishment_type_names[0]
        ? cardData.tm_X3b_und_establishment_type_names[0]
        : '';
      payload.value.recipient.effector_is_sas = cardData.isSasApi;
    }

    async function confirmationSave(date) {
      // temporaire le temps d'avoir la vraie requete
      isLoading.value = true;
      componentName.value = NotificationTab.name;
      getOrientationPayload();
      response.value = await setOrientationRegistration(payload.value);
      componentProps.value = createProps(date, false, response.value.notification);
      isLoading.value = false;
    }

    const confirmationUpdate = (date) => {
      componentName.value = RegistrationTab.name;
      componentProps.value = createProps(date, true);
    };

    const notificationConfirmed = () => {
      if (componentProps.value.type !== 'surnumeraire') {
        refreshSlots();
      }
      emit('close');
      componentName.value = RegistrationTab.name;
      componentProps.value = createProps(new Date());
    };

    // update slots list after orientation feature

    const isFilteredSearch = computed(() => searchDataStore.isFiltered);

    /**
     * Refresh slot without call sas-api again
     */
    function refreshSlots() {
      const newSlotData = {
        data: response.value.currentData,
        slotGuid: componentProps.value.calendarSlotData.slotGuid,
        doctorId: props.cardData.its_nid,
        status: response.value.notification.status,
      };
      searchDataStore.setNewAllResultsWithSlots(newSlotData, isFilteredSearch.value);
    }

    function fetchRecipientType(typeName) {
      if (
        typeName
        && props.cardData
        && props.cardData[typeName]
        && props.cardData[typeName][0]
      ) {
        return props.cardData[typeName][0];
      }
      return '';
    }

    function createProps(date = dayjs(), update = false, notificationContent = {}) {
      const showSasOverbooking = isSasOverbookingFilterChecked.value && userDataStore?.currentUser?.isRegulateurOSNP && props.cardData.bs_sas_participation;
      const isCPTS = props.cardData.its_sas_participation_via === 2;

      const checkPsPhone = () => {
        if (isCPTS && showSasOverbooking && !props.cardData.ss_sas_cpts_phone) {
          return '';
        }

        return props.cardData.final_phone_number || '';
      };

      let cptsInfo = null;
      if (isCPTS && !showSasOverbooking) {
        // We only add the following CPTS informations if we are on the 1st level search
        // This `if` clause is temporary, as the whole search level logic will disappear sooner or later.
        cptsInfo = {
          label: props.cardData.ss_sas_cpts_label,
          phone: props.cardData.ss_sas_cpts_phone,
        };
      }

      const phone = computed(() => (showSasOverbooking && props.cardData.ss_sas_cpts_phone
          ? props.cardData.ss_sas_cpts_phone
          : checkPsPhone()));

      const summary = computed(() => {
        const infos = [{
          label: fetchRecipientType('tm_X3b_und_field_profession_name') || fetchRecipientType('tm_X3b_und_establishment_type_names'),
          phone: phone.value,
        }];

        if (cptsInfo) {
          infos.push({ ...cptsInfo });
        }

        return {
          name: props.cardData?.tm_X3b_und_title ? props.cardData?.tm_X3b_und_title[0] : null,
          href: getDetailsPageUrl(props.cardData),
          infos,
        };
      });

      return {
        summary,
        type: props.type,
        calendarSlotData: props.calendarSlotData,
        date,
        update,
        notification: notificationContent,
        additionalInfo: props.cardData.ss_sas_additional_info ?? '',
      };
    }

    return {
      openModal,
      componentName,
      componentProps,
      registrationUpdated,
      confirmationSave,
      confirmationUpdate,
      notificationConfirmed,
      modalTitle,
      isLoading,
      payload,
      formatDateTime,
      setOrientationRegistration,
      response,
      getDetailsPageUrl,
    };
  },
};
</script>
