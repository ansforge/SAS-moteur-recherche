import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import OrientationConst from '@/const/orientation.const';

import {
  useUserData,
  useSearchData,
  useSasOrientationData,
} from '@/stores';

export default () => {
  const userDataStore = useUserData();
  const searchDataStore = useSearchData();
  const sasOrientationData = useSasOrientationData();

  const currentUser = computed(() => (userDataStore.currentUser));
  const isBsSasParticipationChecked = computed(() => (searchDataStore?.customFilters?.bs_sas_overbooking !== undefined));

  /**
   * SAS information should only be displayed if all the following criteria are met:
   *   - the regulator using the platform is an OSNP
   *   - the health offer associated with this card does participate to the sas
   * @returns {boolean}
   */
  function showSasParticipation(cardData) {
    return (
      currentUser.value.isRegulateurOSNP
      && (cardData.bs_sas_participation || cardData.type === 'cpts')
    );
  }

  const showSuperNumeraryBtn = (cardData) => (
    showSasParticipation(cardData)
    && isBsSasParticipationChecked.value
    && cardData.type !== 'cpts'
  );

  function showAdditionalInfo(cardData) {
    return cardData.ss_sas_additional_info?.trim().length
    && (currentUser.value.isRegulateurOSNP || currentUser.value.isRegulateurIOA);
  }

  const { superNumeraryBtnLabel } = storeToRefs(sasOrientationData);

  const cardTypeOrientation = OrientationConst.SURNUMERAIRE;

  return {
    showSasParticipation,
    showAdditionalInfo,
    showSuperNumeraryBtn,
    superNumeraryBtnLabel,
    cardTypeOrientation,
    currentUser,
  };
};
