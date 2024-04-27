<template>
  <div
    :id="`tabpanel-${currentTab.id}`"
    role="tabpanel"
    tabindex="0"
    :aria-labelledby="currentTab.id"
    class="tabpanel-container"
  >
    <div class="db-addresses">
      <DashboardSettings v-if="isDashBoardSettingsVisible" @refresh-address="fetchAdressList" />

      <template v-if="!cardIsLoading">
        <template v-if="addresses.length > 0">
          <DashboardAddress
            v-for="(address, idx) in addresses"
            :addressCounter="`${idx + 1}`"
            :address="address"
            :key="`address-${idx}`"
            :start="start"
            :end="end"
            :popinSnpSettingsData="popinSnpSettingsData"
          />
        </template>

        <div v-else class="db-empty">
          <p>
            Si vous êtes régulateur et que vous souhaitez utiliser le SAS, rapprochez-vous de votre ARS afin qu'un compte vous soit créé.<br>
            Si vous ne disposez pas de numéro RPPS / Adeli, vous pourrez vous connecter en login / mot de passe une fois votre compte créé.<br>
            Si vous êtes effecteur, vous ne pouvez pas effectuer d'action dans le SAS car vous ne disposez pas de lieux d'exercice sur <a href="https://www.sante.fr/">https://sante.fr</a>
          </p>
        </div>
      </template>
      <template v-if="cardIsLoading || isCurrentUserIsLoading">
        <div class="db-address">
          <div class="db-address-header">
            <div class="db-contact">
              <strong>Le chargement de vos adresses est en cours</strong>
            </div>
          </div>

          <div class="db-address-content d-flex justify-content-center">
            <RingLoader />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import {
 ref, computed, watch,
} from 'vue';
import dayjs from 'dayjs';

import { CalendarService, SearchService, SettingService } from '@/services';
import { useUserDashboard } from '@/stores';
import { useDashboard, useSchedule } from '@/composables';
import { AggregatorPayload } from '@/models';
import { cookie } from '@/helpers';

import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';
import DashboardAddress from './DashboardAddress.component.vue';
import DashboardSettings from './dashboard-settings-component/DashboardSettings.component.vue';

export default {
  components: {
    DashboardSettings,
    DashboardAddress,
    RingLoader,
  },
  props: {
    currentTab: {
      type: Object,
      default: () => ({}),
    },
    currentTabContent: {
      type: Object,
      default: () => ({}),
    },
  },
  setup() {
    const userDashboardStore = useUserDashboard();
    const rppsAdeli = computed(() => userDashboardStore.userRppsAdeli);
    const cardIsLoading = ref(true);

    // fetch user addresses feature
    const addresses = computed(() => userDashboardStore.userAddresses);
    const { getSlotsIds } = useDashboard();
    const { getPayloadDate } = useSchedule();

    const isEditorsChecked = computed(() => userDashboardStore.isEditorsChecked);

    /**
     * create the correct payload for aggreg call from all locations
     */
    function createAgregSlotsPayload() {
      const payLoadStructure = new AggregatorPayload();
      // eslint-disable-next-line no-underscore-dangle
      payLoadStructure._practitionerCards = addresses.value.map((loc) => ({
        nid: loc.sheet_nid,
        line: loc.street,
        zipcode: loc.postcode,
        phone: loc.phone_number,
        latitude: loc.latitude,
        longitude: loc.longitude,
        siret: loc.siret,
        finess: loc.finess,
        rppsRang: loc.rpps_rang,
        rpps: (loc.id_nat?.prefix === '8') ? loc.id_nat.id : '',
        adeli: (loc.id_nat?.prefix === '1') ? loc.id_nat.id : '',
      }));

      return payLoadStructure;
    }

    /**
     * fetch locations and slots from aggreg
     */
    async function fetchAggregatorSlots() {
      const agregV2Payload = createAgregSlotsPayload();

      if (!cookie.getCookie('sas_aggregator_token')) {
        await SettingService.getAggregatorToken();
      }

      const res = await SearchService.getAggregatorV2Results(agregV2Payload);
      return res;
    }

    /**
     * set aggreg calendars to the corresponding address
     * @param {Array} aggregSlots
     */
    function setAggregatorSlots(aggregSlots) {
      const aggregLocations = aggregSlots[0]?.locations ?? [];
      aggregLocations.forEach((location) => userDashboardStore.addCalendarToAddress(location));
    }

    // current user is owner of dashboard check
    const isDashBoardOwner = computed(() => window?.drupalSettings?.sas_vuejs?.isDashboardOwner || false);
    const isDashBoardSettingsVisible = computed(() => isDashBoardOwner.value && addresses.value?.length > 0);
    /**
     * fetch all addresses and data
     */
    async function fetchAdressList() {
      cardIsLoading.value = true;
      await userDashboardStore.getCurrentUserAddresses(rppsAdeli.value);
      await fetchModality();

      if (isEditorsChecked.value) {
        const resAggreg = await fetchAggregatorSlots();
        setAggregatorSlots(resAggreg);
      } else {
        getSlotsIds(addresses.value);
      }

      cardIsLoading.value = false;
    }

    watch(() => rppsAdeli.value, fetchAdressList);

    // show pagination data feature
    const systemTz = dayjs.tz.guess();
    const start = ref(getPayloadDate(dayjs(), systemTz, true));
    const end = ref(getPayloadDate(dayjs(start.value).add(2, 'day').format('YYYY-MM-DD'), systemTz));

    // show modality feature
    const popinSnpSettingsData = ref({});

    /**
     * fetch modality from API
     */
    async function fetchModality() {
      popinSnpSettingsData.value = await CalendarService.getPopinCreateDispoConfig();
    }

    const isCurrentUserIsLoading = computed(() => userDashboardStore.showCurrentUserLoader);

    return {
      cardIsLoading,
      useUserDashboard,
      addresses,
      fetchAdressList,
      start,
      end,
      popinSnpSettingsData,
      isDashBoardOwner,
      isDashBoardSettingsVisible,
      isCurrentUserIsLoading,
    };
  },
};
</script>
