import { ApiPlugin } from '@/plugins';
import { SAS_API_DRUPAL } from '@/const';

export default class HomeClass {
  static async getContent() {
    try {
      const response = await ApiPlugin.get(`${SAS_API_DRUPAL}/homepage`);
      return response.data;
    } catch (e) {
      console.error('Error fetching getContent \n', e);
      return [];
    }
  }
}
