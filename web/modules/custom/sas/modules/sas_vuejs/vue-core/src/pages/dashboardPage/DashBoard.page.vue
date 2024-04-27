<template>
  <div class="dashboard-container container-full">
    <Notification
      v-if="notifData.message"
      v-bind="notifData"
    />
    <div class="dashboard-header-tab">
      <TabNavigation
        :tabList="tabs"
        :currentTab="currentTab"
        :tabListTitle="userDashboardStore.userName"
        @changeTab="currentTab = $event"
        @updateSchedule="getSlotsIds(addresses)"
      />
    </div>
    <DashboardTabContent
      :currentTab="currentTab"
      :currentTabContent="currentTabContent"
    />
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import _isEmpty from 'lodash.isempty';

import { DASHBOARD_TAB_LIST } from '@/const';

import { useUserDashboard } from '@/stores';

import { useDashboard } from '@/composables';

import { cookie, routeHelper } from '@/helpers';

import DashboardService from '@/services/dashboard.service';
import EffectorSettingsModel from '@/models/dashboard/EffectorSettings.model';

import TabNavigation from '@/components/sharedComponents/TabNavigation.component.vue';
import DashboardTabContent from '@/components/dashboardComponents/DashboardTabContent.component.vue';
import Notification from '@/components/sharedComponents/Notification.component.vue';

export default {
  components: {
    TabNavigation,
    DashboardTabContent,
    Notification,
  },
  setup() {
    // tab content
    const tabs = ref(DASHBOARD_TAB_LIST.TAB_LIST || []);
    const currentTab = ref({ id: 'adresses', label: 'Vos adresses' });
    const currentTabContent = computed(() => ({}));

    // fetch current user feature
    const userDashboardStore = useUserDashboard();

    const isDashBoardOwner = computed(() => window?.drupalSettings?.sas_vuejs?.isDashboardOwner || false);

    async function fetchUserData() {
      // user features
      const userParams = routeHelper.getUrlParam('userId') ? { userId: routeHelper.getUrlParam('userId') } : {};
      await userDashboardStore.getCurrentUserData(userParams);

      if (
        !isDashBoardOwner.value
        && routeHelper.getUrlParam('userId')
      ) {
        const res = await DashboardService.getDashboardUserSettings(userDashboardStore.userRppsAdeli);
        const effectorSettings = _isEmpty(res) ? res : new EffectorSettingsModel(res).getSettingsData();

        const sasEnabledEditor = (
          effectorSettings.has_software
          && effectorSettings.hours_available
          && !effectorSettings.editor_disabled
          && (
            effectorSettings.participation_sas_via === 1
            || effectorSettings.participation_sas_via === 2
            || effectorSettings.participation_sas_via === 3
          )
        );

        const sasDisabledEditor = (
          !effectorSettings.editor_disabled
          && effectorSettings.hours_available
          && effectorSettings.participation_sas_via === null
        );

        userDashboardStore.setEditorCheckedStatus(sasEnabledEditor || sasDisabledEditor);
        userDashboardStore.setSosMedecinsCheckedStatus(
          !effectorSettings.has_software
          && !effectorSettings.hours_available
          && effectorSettings.participation_sas_via === 4,
        );
      }
    }

    cookie.removeCookie('sas_aggregator_token');
    fetchUserData();

    // notification data
    const notifData = ref({});

    // refresh schedule on click to addresses tab feature
    // fetch user addresses
    const addresses = computed(() => userDashboardStore.userAddresses);
    const { getSlotsIds } = useDashboard();

    return {
      tabs,
      currentTab,
      currentTabContent,
      notifData,
      userDashboardStore,
      addresses,
      getSlotsIds,
      isDashBoardOwner,
    };
  },
};
</script>
