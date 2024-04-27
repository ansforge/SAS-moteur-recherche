import { ApiPlugin } from '@/plugins';
import {
  API_ADDRESS_DATA_GOUV,
  API_ADDRESS_DATA_GOUV_REVERSE,
} from '@/const/api.const';

export default class AddressService {
  /**
   * Call adresse data gouv API with full address
   * @param fullAddress
   * @returns {Promise<any|{}|{}>}
   */
  static async getAddressData(fullAddress) {
    let res = null;
    try {
      res = await ApiPlugin({
        method: 'GET',
        url: API_ADDRESS_DATA_GOUV,
        params: {
          q: fullAddress || '',
        },
      });
      return res.data || {};
    } catch (e) {
      console.warn('error with address API : ', e);
      return {};
    }
  }

  /**
   * It only gets its information from the very first result returned by the call
   * @returns {object} see here for the complete object: https://adresse.data.gouv.fr/api-doc/adresse
   */
  static async fetchPropertiesByCoordinates(lng, lat) {
    let res = null;
    try {
      res = await ApiPlugin({
        method: 'GET',
        url: API_ADDRESS_DATA_GOUV_REVERSE,
        params: {
          lon: lng || '',
          lat: lat || '',
        },
      });

      return res?.data?.features?.[0]?.properties || {};
    } catch (e) {
      console.warn('error with address API : ', e);
      return '';
    }
  }
}
