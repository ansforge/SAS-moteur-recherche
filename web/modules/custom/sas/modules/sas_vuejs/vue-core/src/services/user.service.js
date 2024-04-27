import { SAS_CURRENT_USER, SAS_API_DRUPAL } from '@/const';
import { ApiPlugin } from '@/plugins';

export default class UserService {
  static async getCurrentUser() {
    try {
      const result = await ApiPlugin.get(`${SAS_CURRENT_USER}`);
      return result.data;
    } catch (e) {
      console.error('Error fetching getCurrentUser \n', e);
      return {};
    }
  }

  static async getUserInfo(params = {}) {
    try {
      // const result = await ApiPlugin.get(`${SAS_API_DRUPAL}/drupal/user/info`);
      const result = await ApiPlugin({
        method: 'GET',
        url: `${SAS_API_DRUPAL}/drupal/user/info`,
        params,
      });
      return result.data;
    } catch (e) {
      console.error('Error fetching getUserInfo \n', e);
      return {};
    }
  }
}
