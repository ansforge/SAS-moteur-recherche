import axios from 'axios';
import { ApiPlugin } from '@/plugins';
import {
  SAS_API_LOCATION_AUTOCOMPLETE,
} from '@/const/api.const';

export default class GeolocationService {
  /**
   *
   * @param {string} name
   * Could be a city name or a postal code
   */
  static async getLocationByName(name, abortSignal) {
    try {
      const response = await ApiPlugin.get(
        `${SAS_API_LOCATION_AUTOCOMPLETE}?searchValue=${name}`,
        { signal: abortSignal },
      );
      return response?.data || {};
    } catch (e) {
      if (!axios.isCancel(e)) {
        console.error('Error fetching locations \n', e);
      }

      return [];
    }
  }
}
