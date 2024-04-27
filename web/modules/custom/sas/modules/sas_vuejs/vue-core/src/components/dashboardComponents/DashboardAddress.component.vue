<template>
  <div class="db-address">
    <div class="db-address-header">
      <div class="db-contact">
        <h2 v-if="showAddressLabel"><strong>{{ currentAddressCountLabel }}</strong>{{ address.address }}</h2>
        <div v-if="showPhone" class="phone" :class="{ 'txt-red': !hasNumber }">
          <strong>Téléphone :</strong> {{ currentPhoneNumber }}
        </div>
      </div>
      <span v-if="address.last_update">Dernière mise à jour le {{ address.last_update }}</span>
    </div>

    <div class="db-address-content" :class="{ 'db-multiple-calendars': address.calendars?.length > 1 }">
      <!-- if no aggreg calendars were found -->
      <DashboardAvailabilities
        v-if="showSnpCalendars"
        :addressData="address"
        :start="start"
        :end="end"
        :popinSnpSettingsData="popinSnpSettingsData"
      />

      <div v-else class="db-aggreg-calendars">
        <!-- aggreg calendars only -->
        <DashboardAvailabilities
          v-for="(calendar, idx) in address.calendars"
          :addressData="address"
          :start="start"
          :end="end"
          :popinSnpSettingsData="popinSnpSettingsData"
          :currentCalendar="calendar"
          :aggregPhone="address.phone_number[idx] ?? null"
          :key="`calendar-aggreg-${idx}`"
        />
      </div>

      <div v-if="showUnavailabilities || !isSosMedecinsChecked" class="db-side-content">
        <DashboardUnavailabilities
          v-if="showUnavailabilities"
          :timeslotNid="address.timeslot_nid || ''"
          :isDisabled="!isBtnActive"
        />

        <DashboardComplementaryInfo
          v-if="!isSosMedecinsChecked"
          :timeslotNid="address.timeslot_nid || ''"
          :isDisabled="!isBtnActive"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import { useUserDashboard } from '@/stores';
import DashboardAvailabilities from './dashboard-address-component/DashboardAvailabilities.component.vue';
import DashboardComplementaryInfo from './dashboard-address-component/DashboardComplementaryInfo.component.vue';
import DashboardUnavailabilities from './dashboard-address-component/DashboardUnavailabilities.component.vue';

export default {
  components: {
    DashboardAvailabilities,
    DashboardUnavailabilities,
    DashboardComplementaryInfo,
  },
  props: {
    address: {
      type: Object,
      default: () => ({}),
    },
    addressCounter: {
      type: String,
      default: '',
    },
    start: {
      type: String,
      default: '',
    },
    end: {
      type: String,
      default: '',
    },
    popinSnpSettingsData: {
      type: Object,
      default: () => ({}),
    },
  },
  setup(props) {
    const userDashboardStore = useUserDashboard();

    const currentAddressCountLabel = computed(() => `Adresse ${props.addressCounter} : `);
    const showAddressLabel = computed(() => currentAddressCountLabel.value && props.address.address);

    const showPhone = computed(() => !isEditorsChecked.value || props.address.calendars?.length < 2);
    const hasNumber = computed(() => (!!props.address.phone_number?.[0]));
    const currentPhoneNumber = computed(() => {
      const numTel = props.address.phone_number?.[0];
      const msgPhone = `N° de téléphone non renseigné${!isEditorsChecked.value ? ', merci de le mettre à jour auprès de votre Ordre' : ''}`;

      return (hasNumber.value && numTel) ? numTel : msgPhone;
    });

    const showUnavailabilities = computed(() => !isEditorsChecked.value && !isSosMedecinsChecked.value);

    const isBtnActive = computed(() => (
      !isEditorsChecked.value
      && !isSosMedecinsChecked.value
      && props.address.calendar_url?.length > 0
    ));

    const isSosMedecinsChecked = computed(() => userDashboardStore.isSosMedecinsChecked);
    const isEditorsChecked = computed(() => userDashboardStore.isEditorsChecked);

    const showSnpCalendars = computed(() => !isEditorsChecked.value || !props.address.calendars?.length);

    return {
      currentAddressCountLabel,
      currentPhoneNumber,
      showUnavailabilities,
      isBtnActive,
      hasNumber,
      isSosMedecinsChecked,
      isEditorsChecked,
      showAddressLabel,
      showPhone,
      showSnpCalendars,
    };
  },
};
</script>
