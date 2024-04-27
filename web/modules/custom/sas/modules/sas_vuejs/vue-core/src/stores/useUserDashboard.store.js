import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { DashboardService, UserService, CalendarService } from '@/services';
import { phoneHelper, distanceByLatLng } from '@/helpers';
import useSchedule from '@/composables/useSchedule.composable';

/* eslint-disable import/prefer-default-export */
export const useUserDashboard = defineStore('useUserDashboard', () => {
  // user & addresses feature
  const connectedUser = ref({});
  const currentUserIsLoading = ref(true);
  const userAddresses = ref([]);
  const isSosMedecinsChecked = ref(false);
  const isEditorsChecked = ref(false);

  async function getCurrentUserData(params = {}) {
    try {
      connectedUser.value = await UserService.getUserInfo(params);
    } catch (err) {
      console.warn('store getCurrentUserData failed');
    }
    currentUserIsLoading.value = false;
  }

  async function getCurrentUserAddresses(rppsAdeli) {
    try {
      const res = await DashboardService.getEffectorAddressList(rppsAdeli);
      userAddresses.value = res.map((addr) => ({
        ...addr,
        calendars: [],
      }));
    } catch (err) {
      console.warn('store getEffectorAddressList failed');
    }
  }

  const userName = computed(() => connectedUser.value.name);
  /**
   * aggreg data correspondance criterias
   */
  const aggregSortingConditions = [
    // check by id structure
    (loc) => {
      if (!loc.rppsRang && !loc.siret && !loc.finess) {
        return null;
      }
      return userAddresses.value.find((addr) => addr.rpps_rang === loc.rppsRang || addr.siret === loc.siret || addr.finess === loc.finess);
    },
    // check by lat lng
    (loc) => {
      if (!loc.latitude || !loc.longitude) {
       return null;
      }
      return userAddresses.value.find((addr) => addr.latitude === loc.latitude && addr.longitude === loc.longitude);
    },
    // check by distance (within 10m radius)
    (loc) => {
      if (!loc.latitude || !loc.longitude) {
       return null;
      }
      return userAddresses.value.find((addr) => loc.latitude && loc.longitude && distanceByLatLng.calculateDistance(addr, loc) <= 10);
    },
    // check by address
    (loc) => {
      if (!loc.line || !loc.city || !loc.zipCode) {
        return null;
      }
      return userAddresses.value.find((addr) => (
          addr.street?.toLowerCase() === loc.line?.toLowerCase()
          && addr.city?.toLowerCase() === loc.city?.toLowerCase()
          && addr.postcode?.toLowerCase() === loc.zipCode?.toLowerCase()
        ));
    },
  ];

  /**
   * finds the matching address for a given aggreg location
   * @param {Object} location
   * @returns {Object} address
   */
  function findAddressForAggregResult(location) {
    let foundLocation = null;
    for (const crit of aggregSortingConditions) {
      foundLocation = crit(location);

      if (foundLocation) break;
    }

    return foundLocation;
  }

  /**
   * finds correspondances for all aggreg results
   * with the given criteria and updates location details
   * if not creates a new location
   * @param {Object} calendar
   */
  function addCalendarToAddress(calendar) {
    const currentAddress = findAddressForAggregResult(calendar);
    const {
      line,
      zipCode,
      city,
      schedule,
      phones,
      rppsRang,
      finess,
      siret,
      latitude,
      longitude,
    } = calendar;
    const address = `${line}, ${zipCode} ${city}`;
    const aggregTel = phones?.[0] ? phoneHelper.formatPhoneNumber(phones[0]) : null;

    if (currentAddress) {
      currentAddress.address = address;
      currentAddress.calendars.push(schedule.days ?? []);

      if (aggregTel) {
        currentAddress.phone_number = currentAddress.calendars.length > 1
        ? [...currentAddress.phone_number, aggregTel]
        : [aggregTel];
      }
    } else {
      /* eslint-disable camelcase */
      const { title, id_nat, last_update } = userAddresses.value?.[0] || {};
      userAddresses.value.push({
        sheet_nid: null,
        title,
        id_nat,
        rpps_rang: rppsRang,
        finess,
        siret,
        address,
        latitude,
        longitude,
        street: line,
        city,
        postcode: zipCode,
        phone_number: aggregTel ? [aggregTel] : [],
        timeslot_nid: null,
        schedule_id: null,
        last_update,
        calendar_url: null,
        calendars: [schedule.days] ?? [],
        isNewAddress: true,
      });
    }
    /* eslint-enable camelcase */
  }

  const userRppsAdeli = computed(() => connectedUser.value.id_nat);

  // this getter must show loader for TabContent & Settings
  const showCurrentUserLoader = computed(() => !!currentUserIsLoading.value);

  // schedules & slots feature
  const allSlots = ref([]);
  const scheduleIsLoading = ref(false);

  const { getPayloadDate } = useSchedule();
  const systemTz = dayjs.tz.guess();
  const start = ref(getPayloadDate(dayjs(), systemTz, true));
  const end = ref(getPayloadDate(dayjs(start.value).add(2, 'day').format('YYYY-MM-DD'), systemTz));

  async function setSlots(scheduleIds) {
    if (scheduleIds.length) {
      scheduleIsLoading.value = true;
      const paramsConfig = {
        scheduleIds,
        startDate: start.value,
        endDate: end.value,
        orientationStrategy: 0,
        showExpired: 0,
      };
      try {
        allSlots.value = await CalendarService.getAllSlotsByScheduleIds(
          paramsConfig,
        );
      } catch (e) {
        console.error(e);
      } finally {
        scheduleIsLoading.value = false;
      }
    } else {
      allSlots.value = [];
    }
  }

  const getSlots = computed(() => allSlots.value);

  function getSlotsByAddress(scheduleId) {
    return Object.keys(allSlots.value).find((keyId) => (keyId === scheduleId ? allSlots.value[keyId] : []));
  }

  function setSosMedecinsCheckedStatus(status) {
    isSosMedecinsChecked.value = status;
  }

  function setEditorCheckedStatus(status) {
    isEditorsChecked.value = (status || status === undefined);
  }

  return {
    connectedUser,
    getCurrentUserData,
    userName,
    userRppsAdeli,
    showCurrentUserLoader,
    getCurrentUserAddresses,
    userAddresses,
    allSlots,
    scheduleIsLoading,
    getSlots,
    setSlots,
    getSlotsByAddress,
    isSosMedecinsChecked,
    isEditorsChecked,
    setSosMedecinsCheckedStatus,
    setEditorCheckedStatus,
    addCalendarToAddress,
  };
});
