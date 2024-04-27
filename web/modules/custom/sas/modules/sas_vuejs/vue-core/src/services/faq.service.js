import { ApiPlugin } from '@/plugins';

export default class FaqClass {
  static async getFaq() {
    let res = null;
    try {
      res = await ApiPlugin.get('/sas/api/sas-api/faq');
      return res?.data?.data;
    } catch (e) {
      console.error('Error during the orientation registration : ', e);
      return {};
    }
  }

  static async uploadFormData(formData) {
    let res = null;
    try {
      res = await ApiPlugin.post('/sas/api/sas-api/faq_message', formData);
      return res.data;
    } catch (e) {
      console.error('Error during the orientation registration : ', e);
      return {};
    }
  }
}
