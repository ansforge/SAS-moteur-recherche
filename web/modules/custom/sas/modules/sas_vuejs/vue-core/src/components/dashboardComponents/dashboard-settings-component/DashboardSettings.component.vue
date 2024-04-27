<template>
  <template v-if="!cardIsLoading">
    <!-- Parametres si pas de parametres enregistrés-->
    <div v-if="isEffectorSettingsEmpty" class="db-parameters no-parameter">
      <h2>Veuillez renseigner vos paramètres</h2>
      <button class="btn-highlight" type="button" @click="openSettingsModal()">Éditer</button>
    </div>

    <!-- Parametres si parametres enregistrés-->
    <div v-else class="db-parameters has-parameters">
      <h2>Vos paramètres</h2>
      <div class="db-parameters-list">
        <ul class="resetul list-service-data">
          <li :class="[effectorSettings.participation_sas ? 'on' : 'off']">Participation au SAS</li>
          <li :class="[effectorSettings.editor_disabled ? 'off' : 'on']">{{ editorStatusText }}</li>
          <li :class="[effectorSettings.forfait_reo_enabled ? 'on' : 'off']">Forfait de réorientation</li>
        </ul>
        <button class="btn-highlight" type="button" @click="openSettingsModal()">Éditer</button>
      </div>
    </div>

    <DashboardSettingsModal
      v-if="showModal"
      :open="showModal"
      :settings="effectorSettings"
      :rppsAdeli="rppsAdeli"
      @close="showModal = false"
      @refresh="refreshComponent()"
    />
  </template>

  <template v-if="cardIsLoading || isCurrentUserIsLoading">
    <div class="db-parameters has-parameters">
      <strong class="loading-title">Le chargement de vos paramètres est en cours</strong>
      <RingLoader />
    </div>
  </template>
</template>

<script>
import {
 computed, ref, watchEffect, nextTick,
} from 'vue';
import _isEmpty from 'lodash.isempty';
import DashboardService from '@/services/dashboard.service';
import EffectorSettingsModel from '@/models/dashboard/EffectorSettings.model';
import { useUserDashboard } from '@/stores';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';
import DashboardSettingsModal from './DashboardSettingsModal.component.vue';

export default {
    components: {
      DashboardSettingsModal,
      RingLoader,
    },
    emits: ['refresh-address'],
    setup(props, { emit }) {
        const effectorSettings = ref({});
        const isEffectorSettingsEmpty = computed(() => _isEmpty(effectorSettings.value));
        // get editor value text
        const editorStatusText = computed(() => (effectorSettings.value.editor_disabled ? 'Créneaux éditeurs non affichés' : 'Créneaux éditeurs affichés'));

        // lazy loading feature
        const cardIsLoading = ref(true);
        const isCurrentUserIsLoading = computed(() => userDashboardStore.showCurrentUserLoader);

        // settings call API feature
        const userDashboardStore = useUserDashboard();
        const rppsAdeli = computed(() => userDashboardStore.userRppsAdeli);

        async function getSettings() {
          const res = await DashboardService.getDashboardUserSettings(rppsAdeli.value);
          effectorSettings.value = _isEmpty(res) ? res : new EffectorSettingsModel(res).getSettingsData();
          cardIsLoading.value = false;

          const sasEnabledEditor = (
            effectorSettings.value.has_software
            && effectorSettings.value.hours_available
            && !effectorSettings.value.editor_disabled
            && (
              effectorSettings.value.participation_sas_via === 1
              || effectorSettings.value.participation_sas_via === 2
              || effectorSettings.value.participation_sas_via === 3
            )
          );

          const sasDisabledEditor = (
            !effectorSettings.value.editor_disabled
            && effectorSettings.value.hours_available
            && effectorSettings.value.participation_sas_via === null
          );

          userDashboardStore.setEditorCheckedStatus(sasEnabledEditor || sasDisabledEditor);
          userDashboardStore.setSosMedecinsCheckedStatus(
            !effectorSettings.value.has_software
            && !effectorSettings.value.hours_available
            && effectorSettings.value.participation_sas_via === 4,
          );
        }

        watchEffect(() => rppsAdeli.value, nextTick(getSettings));

        // modal feature
        const showModal = ref(false);
        function openSettingsModal() {
          showModal.value = true;
        }

        /**
         *  refresh component after save/success action
         */
        function refreshComponent() {
          getSettings();
          emit('refresh-address');
        }

        return {
          rppsAdeli,
          effectorSettings,
          cardIsLoading,
          useUserDashboard,
          openSettingsModal,
          showModal,
          refreshComponent,
          editorStatusText,
          isCurrentUserIsLoading,
          isEffectorSettingsEmpty,
        };
    },
};
</script>
