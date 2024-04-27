import { nextTick } from 'vue';
import { SearchService } from '@/services';

import {
  useLrmData,
  useSearchData,
} from '@/stores';

import usePayload from './usePayload.composable';
import useAnalytics from './useAnalytics.composable';
import useSearchApiCalls from './useSearchApiCalls.composable';

export default () => {
  const { createSearchPayload } = usePayload();
  const { launchLogsForLrm } = useAnalytics();
  const { getApiResults } = useSearchApiCalls();
  const lrmDataStore = useLrmData();
  const searchDataStore = useSearchData();

  function setPrefDoctorPromise() {
    const payloadSettings = {
      text: lrmDataStore.speciality,
      loc: lrmDataStore.address,
      isLrmSearchWithPreferredDoctor: lrmDataStore.isLrmSearchWithPreferredDoctor,
      prefDoctor: lrmDataStore.prefDoctorParam,
    };

    const prefDoctorSearchParams = createSearchPayload({
      filters: searchDataStore.currentSelectedFilters,
      paginationData: searchDataStore.paginationData,
      settings: payloadSettings,
    });

    return SearchService.getPrefDoctor(prefDoctorSearchParams);
  }

  /**
   * set current search position && preferred doctor data
   */
  async function configureSearchPrefDoctor() {
    if (!lrmDataStore.isLrmSearch) return;

    const prefDoctorData = await setPrefDoctorPromise();

    const hasLrm = Array.isArray(prefDoctorData);

    // verify if lrm dr data exist or if there is an error
    const results = hasLrm
      ? await getApiResults(prefDoctorData)
      : prefDoctorData;

      lrmDataStore.setPreferredDoctorData(results);

    // adds pref doctor to the (empty) filtered list if it has been found
    nextTick(() => {
      if (lrmDataStore.displayPreferredDoctor) {
        searchDataStore.setPrefDoctorToResults(lrmDataStore.preferredDoctorData);
      }

      // logs for analytic
      launchLogsForLrm();
    });
  }

  return {
    configureSearchPrefDoctor,
  };
};
