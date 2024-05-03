import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { ApiPlugin } from '@/plugins';
import DashboardClass from '@/services/dashboard.service';
import {
  SAS_API,
  SAS_API_CONFIG,
  SAS_JSON_API,
  SAS_API_DRUPAL,
  SAS_ADDITIONAL_INFO,
} from '@/const';

dayjs.extend(utc);

export default class CalendarClass {
  static getScheduleId() {
    return window?.API?.['time-slot-schedule']?.schedule_id ? Number(window?.API?.['time-slot-schedule']?.schedule_id) : null;
  }

  static getNodeId() {
    return window?.API?.['time-slot-schedule']?.node_id || null;
  }

  static isCentreDeSante() {
    return window?.API?.['time-slot-schedule']?.is_cds || null;
  }

  /**
   * fetch slots for calendar page or deep page
   * @param {*} config
   * @returns
   */
  static async getSlotsByScheduleId(config = {}) {
    if (!config.startDate || !config.endDate) {
      throw Error('"id" and "start" are required on getSlotsByScheduleId function');
    }
    if (!config.scheduleId) {
      console.warn('No schedule id was sent to getSlotsByScheduleId');
      return [];
    }
    let res = null;
    const today = dayjs().format('YYYY-MM-DD');
    const uri = config.context !== 'calendar' ? `${SAS_JSON_API}/get-slots-by-schedule/${config.scheduleId}/without-unavailabilities` : `${SAS_API}/schedule/${config.scheduleId}`;
    try {
      res = await ApiPlugin.get(
        uri,
        {
          params: {
            start_date: config.startDate === today ? dayjs(config.startDate).utc().format('YYYY-MM-DDTHH:mm:ss') : config.startDate,
            end_date: config.endDate,
            orientationStrategy: config.orientationStrategy,
            show_expired: config.showExpired,
          },
        },
      );
    } catch (e) {
      console.error('Error fetching getSlotsByScheduleId \n', e);
    }

    // get-slots-by-schedule endpoint get value in res instead of schedule endpoint
    return config.context !== 'calendar' ? res?.data : res?.data?.data;
  }

  static async getAllSlotsByScheduleIds(config = {}) {
    if (!config.startDate || !config.endDate) {
      throw Error('"id" and "start" are required on getAllSlotsByScheduleIds function');
    }
    if (!config.scheduleIds) {
      console.warn('No schedule id was sent to getAllSlotsByScheduleIds');
      return [];
    }
    let res = null;
    const today = dayjs().format('YYYY-MM-DD');
    try {
      res = await ApiPlugin.post(
        `${SAS_API}/get_slots_by_schedule_ids`,
        {
          schedule_ids: config.scheduleIds,
        },
        {
          params: {
            start_date: config.startDate === today ? dayjs(config.startDate).utc().format('YYYY-MM-DDTHH:mm:ss') : config.startDate,
            end_date: config.endDate,
            orientationStrategy: config.orientationStrategy,
            show_expired: config.showExpired,
          },
        },
      );
    } catch (e) {
      console.error('Error fetching getAllSlotsByScheduleIds \n', e);
    }
    return res?.data?.data || {};
  }

  static async getPopinCreateDispoConfig() {
    let res = null;
    try {
      res = await ApiPlugin.get(`${SAS_API_CONFIG}/config_group/snp`);
    } catch (e) {
      console.error('Error fetching getPopinCreateDispoConfig \n', e);
    }
    return res?.data?.data?.find((o) => o.name === 'popin_snp')?.value || [];
  }

  static async getIndispoByNodeId(nodeId) {
    try {
      const res = await ApiPlugin.get(`${SAS_JSON_API}/unavailability/${nodeId}`);
      return res?.data || {};
    } catch (e) {
      console.error('Error fetching getIndispoByNodeId', e);
      return {};
    }
  }

  static async fetchAdditionalInformationText(nodeId, idNat) {
    try {
      const res = await ApiPlugin.get(
        `${SAS_API_DRUPAL}${SAS_ADDITIONAL_INFO}?nid=${nodeId}&national_id=${idNat}`,
      );
      return res.data?.additional_info ?? '';
    } catch (e) {
      console.error('Error fetching additional information text', e);
      return '';
    }
  }

  /**
   * Submits additional info message
   * @param {Object} params
   * {nid: null | number , additional_data: string, national_id: string }
   * @returns {Object}
   */
  static async submitAdditionalInformationText(params) {
    const token = await DashboardClass.getDrupalToken();

    try {
      const res = await ApiPlugin.post(
        `${SAS_API_DRUPAL}${SAS_ADDITIONAL_INFO}`,
        params,
        {
          headers: {
            common: {
              'X-CSRF-TOKEN': token,
            },
          },
        },
      );
      return res?.data || null;
    } catch (e) {
      throw new Error(e);
    }
  }

  static async postIndispo(nodeId, vacationMode = false, dates = []) {
    try {
      const res = await ApiPlugin.post(
        `${SAS_JSON_API}/unavailability/${nodeId}`,
        {
          vacation_mode: vacationMode,
          dates,
        },
      );
      return res?.data || null;
    } catch (e) {
      throw new Error(e);
    }
  }

  static async postSlot(form, nodeId) {
    try {
      const res = await ApiPlugin.post(`${SAS_JSON_API}/slot/${nodeId}`, form);
      return res?.data || null;
    } catch (e) {
      throw new Error(e);
    }
  }

  static async putSlot(form, nodeId) {
    try {
      const res = await ApiPlugin.put(`${SAS_JSON_API}/slot/${nodeId}`, form);
      return res?.data || null;
    } catch (e) {
      throw new Error(e);
    }
  }

  static async deleteSlot(nodeId, {
    id, type, disableAllOccurences, date,
  }) {
    try {
      const res = await ApiPlugin.post(`${SAS_JSON_API}/delete-slot/${nodeId}`, {
        snp_delete_slot: !disableAllOccurences,
        slot_type: type,
        slot_id: id,
        date,
      });
      return res?.data || null;
    } catch (e) {
      throw new Error(e);
    }
  }

  static async fetchAdditionalInformationAlertMsg(nodeId) {
    try {
      const res = await ApiPlugin.get(`${SAS_JSON_API}/additional-information/config/${nodeId}`);
      return res.data || '';
    } catch (e) {
      console.error('Error fetching additional information alert message', e);
      return '';
    }
  }

  static getAgregSlots(placeNid) {
    const res = window.drupalSettings?.['aggreg-ps-calendar']?.[placeNid] || {};
    const slots = [];
    Object.values(res).forEach((day) => {
      day.forEach((slot) => {
        slots.push(slot);
      });
    });

    return slots;
  }

  static async getPopinDeleteConfig() {
    try {
      const res = await ApiPlugin.get(`${SAS_API_CONFIG}/config_group/group_suppression_snp`);
      const { data } = res.data;
      if (!data.length) {
        throw Error('data array is empty, and is required on getPopinDeleteConfig function');
      }
      return {
        title: data[0].value.title,
        subtitle: data[0].value.subtitle,
      };
    } catch (e) {
      console.error('Errors on getPopinDeleteConfig', e);
      return {
        title: '',
        subtitle: '',
      };
    }
  }
}
