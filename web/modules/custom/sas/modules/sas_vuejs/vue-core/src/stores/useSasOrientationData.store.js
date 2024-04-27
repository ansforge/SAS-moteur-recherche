import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

/**
 * The store responsible for storing the miscelaneous labels used in the SAS.
 * Thus every label can be freely provided depending of the requirement of the app.
 * Here, they are populated via api calls inside the Search page onMounted method
 */
/* eslint-disable import/prefer-default-export */
export const useSasOrientationData = defineStore('SasOrientationData', () => {
  const reorientationSettings = ref({});
  const sasParticipationSettings = ref({});
  const popinSnpSettings = ref({});
  const orientationSettings = ref({});

  function setReorientationSettings(settings) {
    reorientationSettings.value = settings;
  }

  function setSasParticipationSettings(settings) {
    sasParticipationSettings.value = settings;
  }

  function setPopinSnpSettings(settings) {
    popinSnpSettings.value = settings;
  }

  function setOrientationSettings(settings) {
    orientationSettings.value = settings;
  }

  const sasParticipationLabel = computed(() => sasParticipationSettings.value?.value?.pictogram_label);
  const sasParticipationFilterLabel = computed(() => sasParticipationSettings.value?.value?.filter_label);

  const sasForfaitReuLabel = computed(() => reorientationSettings.value?.value?.pictogram_label);
  const sasForfaitReuFilterLabel = computed(() => reorientationSettings.value?.value?.filter_label);

  const superNumeraryBtnLabel = computed(() => (orientationSettings.value?.value?.supernumerary_button));

  return {
    reorientationSettings,
    sasParticipationSettings,
    popinSnpSettings,
    orientationSettings,
    sasParticipationLabel,
    sasForfaitReuLabel,
    sasParticipationFilterLabel,
    sasForfaitReuFilterLabel,
    superNumeraryBtnLabel,
    setReorientationSettings,
    setSasParticipationSettings,
    setPopinSnpSettings,
    setOrientationSettings,
  };
});
