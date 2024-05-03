// eslint-disable-next-line import/prefer-default-export
import {
  SAS_CONFIGURATION,
  SAS_AGGREGATOR_TOKEN,
  SAS_DICTIONNARY_FILTRE,
  SAS_TERRITORIES,
  SAS_LRM_SEARCH_TRACKING,
} from '@/const';
import { ApiPlugin } from '@/plugins';
import { cookie } from '@/helpers';

export default class SettingClass {
  static async getDictionaryFilter() {
    try {
      const res = await ApiPlugin.get(`${SAS_DICTIONNARY_FILTRE}`);
      return res.data;
    } catch (e) {
      console.error('Error fetching getSettings \n', e);
      return {};
    }
  }

  static async getSasApiSettingsByParam(settingsParam) {
    try {
      const result = await ApiPlugin.get(`${SAS_CONFIGURATION}/${settingsParam}`);
      return result?.data?.data;
    } catch (e) {
      console.error('Error fetching getSettings \n', e);
      return {};
    }
  }

  /**
   * It also set the token in a temporary cookie on success
   * @returns {string} the token
   */
  static async getAggregatorToken() {
    try {
      const res = await ApiPlugin.post(SAS_AGGREGATOR_TOKEN);
      const token = res?.data?.data || null;

      cookie.setCookie('sas_aggregator_token', token, 55 * 60);
      return token;
    } catch (e) {
      console.error('Error fetching get aggreg token \n', e);
      return {};
    }
  }

  static async postSearchLog(searchType, regulatorTerritories) {
    try {
      await ApiPlugin.post(
        SAS_LRM_SEARCH_TRACKING,
        {
          logName: 'AnalyticsFrontSearch',
          date: new Date().toISOString(),
          origin: 'sas-front',
          content: {
            regulatorSas: regulatorTerritories || [],
            searchType: searchType || '',
          },
        },
      );
    } catch (e) {
      console.error('Error fetching post search log', e);
    }
  }

  static async getTerritories() {
    try {
      const result = await ApiPlugin.get(`${SAS_TERRITORIES}`);
      return result?.data?.data;
    } catch (e) {
      console.error('Error fetching getTerritories \n', e);
      return {};
    }
  }
}
