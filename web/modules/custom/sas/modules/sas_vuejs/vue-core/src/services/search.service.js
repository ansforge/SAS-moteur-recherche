import { ApiPlugin } from '@/plugins';
import { cookie, routeHelper } from '@/helpers';
import {
  SAS_JSON_API,
  SAS_SOLR,
  SAS_AGGREGATOR,
  SAS_DASHBOARD_AGGREGATOR_SLOTS,
  SAS_STRUCTURE_MAPPING,
 } from '@/const';

export default class SearchClass {
  static async getPrefDoctor(currentParams) {
    try {
      const result = await ApiPlugin.get(`${SAS_SOLR}/pref-doctor?${currentParams}`);
      return result?.data;
    } catch (e) {
      console.error('Error fetching pref doctor \n', e);
      return { error: 'Error fetching pref doctor' };
    }
  }

  static async getSearchResults(payload) {
    // have to remove all quotations marks added by the payload model
    const currentParams = decodeURI(new URLSearchParams(payload).toString());

    try {
      const result = await ApiPlugin.get(`${SAS_SOLR}?${currentParams}`);
      return result?.data;
    } catch (e) {
      console.error('Error fetching getResults \n', e);
      return { error: 'Error fetching getResults' };
    }
  }

  static async getAggregatorResults(payload) {
    try {
      const res = await ApiPlugin.post(
        SAS_AGGREGATOR,
        payload,
        {
          headers: {
            Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
          },
        },
      );
      return res.data || null;
    } catch (e) {
      console.error('Error fetching getAggregatorResults \n', e);
      return {};
    }
  }

  static async getAggregatorV2Results(payload) {
    try {
      const res = await ApiPlugin.post(
        SAS_DASHBOARD_AGGREGATOR_SLOTS,
        payload,
        {
          headers: {
            Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
          },
        },
      );
      return res.data || null;
    } catch (e) {
      console.error('Error fetching getAggregatorResults \n', e);
      return {};
    }
  }

  static async getSasResults(sasApiPayload, start, end) {
    try {
      const res = await ApiPlugin.post(
        `${SAS_JSON_API}/get-slots-by-ps`,
        sasApiPayload,
        {
          params: {
            start_date: start,
            end_date: end,
          },
        },
      );
      return res?.data || null;
    } catch (e) {
      console.error('Error fetching getAvailabilitiesResults \n', e);
      return {};
    }
  }

  static async getStructureMapping(what) {
    try {
      const res = await ApiPlugin.get(
        `${SAS_STRUCTURE_MAPPING}?search_text=${what || routeHelper.getUrlParam('text')}`,
      );
      return res?.data || null;
    } catch (e) {
      console.error('Error fetching getResults \n', e);
      return {};
    }
  }
}
