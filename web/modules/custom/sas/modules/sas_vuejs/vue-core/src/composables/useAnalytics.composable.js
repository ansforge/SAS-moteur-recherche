import { useLrmData } from '@/stores';
import { LrmService } from '@/services';
import { routeHelper } from '@/helpers';

export default () => {
  const lrmDataStore = useLrmData();

  /**
   * 3 logs for LRM analytics :
   *  - redirect to  : search_page/search_page_mt
   *  - LrmPractitionerUrlFormat : {notTransmitted: 0/1, fullText: 0/1, normed: 0/1}
   *  - LrmSpecialtyUrlFormat : {notTransmitted: 0/1, fullText: 0/1, normed: 0/1}
   */
  function launchLogsForLrm() {
    LrmService.trackingLrmSolrResponse(
      !lrmDataStore.isLrmSearchWithPreferredDoctor || !lrmDataStore.displayPreferredDoctor,
      routeHelper.getUrlParam('origin'),
    );
    LrmService.trackingLrmUrlFormat(true, lrmDataStore.doctorTrackingContent);
    LrmService.trackingLrmUrlFormat(false, lrmDataStore.specialtyTrackingContent);
  }

  return {
    launchLogsForLrm,
  };
};
