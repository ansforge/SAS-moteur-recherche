import { useUserDashboard } from '@/stores';

export default () => {
    /**
     * check value and show error text
     * @param {*} val
     * @param {*} inputVal
     * @returns
     */
    const autocompleteValueCheck = (val, inputVal) => {
      const sasViaKey = val;
      let errorTextSasVia = '';
      if (sasViaKey === 2) {
        errorTextSasVia = 'Veuillez sélectionner une CPTS dans les suggestions qui vous sont proposées.';
      } else if (sasViaKey === 3) {
        errorTextSasVia = 'Veuillez sélectionner une MSP dans les suggestions qui vous sont proposées.';
      } else if (sasViaKey === 4) {
        errorTextSasVia = 'Veuillez sélectionner une association SOS Médecins dans les suggestions qui vous sont proposées.';
      }

      const checkValue = /\(\d*?\)/.test(inputVal);

      return {
        check: checkValue,
        error: !checkValue ? errorTextSasVia : '',
     };
    };

    const userStore = useUserDashboard();

    async function getSlotsIds(addresses) {
      let scheduleIds = [];

      if (!userStore.isSosMedecinsChecked && !userStore.isEditorsChecked) {
        scheduleIds = addresses.map((address) => address.schedule_id);
      }

      await userStore.setSlots(scheduleIds);
    }

    return {
      autocompleteValueCheck,
      getSlotsIds,
    };
};
