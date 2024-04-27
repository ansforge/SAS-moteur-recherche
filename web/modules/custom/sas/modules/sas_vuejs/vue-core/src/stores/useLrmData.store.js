import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

import { routeHelper } from '@/helpers';
import { LrmService } from '@/services';

/**
 * This store is responsible of everything related to the LRM.
 * Check here for the full documentation: https://kleegroup.atlassian.net/wiki/spaces/SAS/pages/203494922/LRM+-+Logiciels+de+r+gulation+m+dicale
 */
/* eslint-disable import/prefer-default-export */
export const useLrmData = defineStore('lrmData', () => {
  const isLrmSearch = ref(!!routeHelper.getUrlParam('origin'));
  const isLrmSearchWithPreferredDoctor = ref(isLrmSearch.value && !!routeHelper.getUrlParam('practitioner'));

  const speciality = ref('');

  const prefDoctorParam = ref('');
  const preferredDoctorResponseError = ref({});
  const preferredDoctorData = ref([]);
  const displayPreferredDoctor = ref(true);

  // Analytics feature
  const doctorTrackingContent = ref({
    notTransmitted: 0,
    fullText: 0,
    normed: 0,
  });
  const specialtyTrackingContent = ref({
    notTransmitted: 0,
    fullText: 0,
    normed: 0,
  });

  const address = computed(() => ((isLrmSearch.value && window.API?.location_input) ? window.API.location_input : ''));

  function setPreferredDoctorData(drData) {
    if (Array.isArray(drData)) {
      preferredDoctorData.value = drData.map((x) => ({
        ...x,
        isLrmSearchWithPreferredDoctor: isLrmSearchWithPreferredDoctor.value,
      }));
    } else {
      preferredDoctorResponseError.value = drData;
      updateDisplayPreferredDoctor();
    }
  }

  async function setSpeciality() {
    if (!isLrmSearch.value) {
      speciality.value = '';
      return;
    }

    const defaultValue = 'Consultation de médecine générale';
    const specialityParam = routeHelper.getUrlParam('specialty');
    specialtyTrackingContent.value.notTransmitted = specialityParam === '' ? 1 : 0;
    specialtyTrackingContent.value.fullText = specialityParam === '' ? 0 : 1;

    const regex = /^urn:oid:(\d+(.\d+)+)[|][A-Za-z0-9]+$/;
    if (regex.test(specialityParam)) {
      const specialityCodeSm = specialityParam.replace('urn:oid:', '');
      speciality.value = (await LrmService.getSpecialityName(specialityCodeSm)).label || defaultValue;
      specialtyTrackingContent.value.fullText = 0;
      specialtyTrackingContent.value.normed = 1;
    } else {
      speciality.value = specialityParam || defaultValue;
    }
  }

  function setNormedPrefDoctor() {
    const prefDoctorSplited = prefDoctorParam.value.split('|');
    const firstCharacter = prefDoctorSplited[1][0];

    doctorTrackingContent.value.normed = 1;
    doctorTrackingContent.value.fullText = 0;

    const hasCorrectPrefDr = (firstCharacter === '8' && prefDoctorSplited[1].substring(1).length === 11);
    prefDoctorParam.value = hasCorrectPrefDr ? prefDoctorSplited[1].substring(1) : '';

    if (!hasCorrectPrefDr) {
      preferredDoctorResponseError.value = { error_code_sas: 'sas_pf_001' };
      updateDisplayPreferredDoctor();
    }
  }

  function setPrefDoctorParam() {
    if (!isLrmSearch.value) return;

    if (!isLrmSearchWithPreferredDoctor.value) {
      console.warn('`practitioner` is missing from the url parameter');
      preferredDoctorResponseError.value = { error_code_sas: 'sas_pf_002' };
      updateDisplayPreferredDoctor();
      return;
    }

    prefDoctorParam.value = routeHelper.getUrlParam('practitioner');

    doctorTrackingContent.value.notTransmitted = prefDoctorParam.value === '' ? 1 : 0;
    doctorTrackingContent.value.fullText = prefDoctorParam.value === '' ? 0 : 1;

    // SAS-4419 if practitioner with code rpps
    if (prefDoctorParam.value.startsWith('urn:oid:1.2.250.1.71.4.2.1')) {
      setNormedPrefDoctor();
    } else if (
      prefDoctorParam.value.includes('urn')
      || prefDoctorParam.value.includes('oid')
      || prefDoctorParam.value.includes(':')
    ) {
      preferredDoctorResponseError.value = { error_code_sas: 'sas_pf_001' };
      updateDisplayPreferredDoctor();
    }
  }

  function updateDisplayPreferredDoctor() {
    if (preferredDoctorResponseError.value.error_code_sas === 'sas_pf_001') {
      displayPreferredDoctor.value = false;
    }
  }

  return {
    isLrmSearch,
    isLrmSearchWithPreferredDoctor,
    preferredDoctorData,
    preferredDoctorResponseError,
    speciality,
    address,
    prefDoctorParam,
    displayPreferredDoctor,
    doctorTrackingContent,
    specialtyTrackingContent,

    setPreferredDoctorData,
    setSpeciality,
    setPrefDoctorParam,
  };
});
