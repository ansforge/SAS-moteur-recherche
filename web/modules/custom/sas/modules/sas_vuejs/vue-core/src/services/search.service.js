import { ApiPlugin } from '@/plugins';
import { cookie, routeHelper } from '@/helpers';
import {
  SAS_JSON_API,
  SAS_SOLR,
  SAS_AGGREGATOR,
  SAS_DASHBOARD_AGGREGATOR_SLOTS,
  SAS_STRUCTURE_MAPPING,
  SAS_AGGREGATOR_MARKETPLACE_EDITOR_LIST,
  SAS_CPTS_LIST,
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

  /**
   * This function is both used by regular and CPTS search
   * @param {string} payload
   */
  static async getSearchResults(payload, baseUrl = SAS_SOLR) {
    // have to remove all quotations marks added by the payload model
    const currentParams = decodeURI(new URLSearchParams(payload).toString());

    try {
      const result = await ApiPlugin.get(`${baseUrl}?${currentParams}`);
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
            Authorization: `bearer ${cookie.getCookie('sas_aggregator_token')}`,
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
            Authorization: `bearer ${cookie.getCookie('sas_aggregator_token')}`,
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

  static async fetchMarketPlaceActiveEdtiorsList() {
    try {
      const res = await ApiPlugin.get(
        `${SAS_AGGREGATOR_MARKETPLACE_EDITOR_LIST}?enabled=1&format=marketplace_short_description`,
        {
          headers: {
            Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
          },
        },
      );
      return res.data || [];
    } catch (e) {
      console.error('Error fetching market place editor list \n', e);
      return [];
    }
  }

  /**
   * fetch the list of cpts attached to the given code insee
   * @param {String} inseeCode
   * @returns {Promise<import('@/types').CPTSCard[]>} array of CPTS
   */
  static async fetchCPTSListByInseeCode(inseeCode) {
    try {
      const result = await ApiPlugin.get(`${SAS_CPTS_LIST}?code_insee=${inseeCode}`);
      return result?.data || [];
    } catch (e) {
      console.error(`Error fetching CPTS list with ${inseeCode}\n`, e);
      return [];
    }
  }
}
