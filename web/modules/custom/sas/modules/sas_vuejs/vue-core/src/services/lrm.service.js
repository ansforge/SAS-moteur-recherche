import { ApiPlugin } from '@/plugins';
import { SAS_SPECIALITY_REPOSITORY, SAS_LRM_SEARCH_TRACKING } from '@/const';

export default class LrmService {
  static async getSpecialityName(id) {
    try {
      const res = await ApiPlugin.get(`${SAS_SPECIALITY_REPOSITORY}/${id}`);
      return res?.data?.data || {};
    } catch (e) {
      console.error('Error fetching getSpecialityName', e);
      return {};
    }
  }

  static async trackingLrmSolrResponse(LrmSearchWithoutMT, originParam) {
    try {
      ApiPlugin.post(
        SAS_LRM_SEARCH_TRACKING,
        {
          logName: 'LrmRedirectionStatus',
          date: new Date().toISOString(),
          origin: 'sas-front',
          content: {
            origin: originParam,
            redirectTo: !LrmSearchWithoutMT ? 'search_page_mt' : 'search_page',
            url: window.location.href,
          },
        },
      );
    } catch (e) {
      console.error('Error posting lrm search tracking', e);
    }
  }

  static async trackingLrmUrlFormat(isPractitioner, dataUrl) {
    try {
      ApiPlugin.post(
        SAS_LRM_SEARCH_TRACKING,
        {
          logName: `Lrm${isPractitioner ? 'Practitioner' : 'Specialty'}UrlFormat`,
          date: new Date().toISOString(),
          origin: 'sas-front',
          content: {
            notTransmitted: dataUrl.notTransmitted,
            fullText: dataUrl.fullText,
            normed: dataUrl.normed,
          },
        },
      );
    } catch (e) {
      console.error('Error posting lrm url format tracking', e);
    }
  }
}
