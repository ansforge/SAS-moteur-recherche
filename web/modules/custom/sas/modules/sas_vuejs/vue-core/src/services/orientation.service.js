import { ApiPlugin } from '@/plugins';
import { SAS_API_ORIENTATION } from '@/const';

export default class UserService {
  static async postOrientation(orientationPayload) {
    const payload = orientationPayload;
    let res = null;
    try {
      res = await ApiPlugin.post(`${SAS_API_ORIENTATION}`, payload);
      return res.data;
    } catch (e) {
      console.error('Error during the orientation registration : ', e);
      return {};
    }
  }
}
