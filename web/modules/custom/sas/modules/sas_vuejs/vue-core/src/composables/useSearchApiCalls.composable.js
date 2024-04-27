import dayjs from 'dayjs';
import { formatTimeZoneToHour } from '@/helpers';
import { SearchModel } from '@/models';
import { SearchService } from '@/services';
import {
  useSearchData,
  useLrmData,
  useSearchType,
  useGeolocationData,
} from '@/stores';
import usePayload from './usePayload.composable';

export default () => {
  const { createSearchPayload, createSasApiPayload, createAggregatorPayload } = usePayload();

  const lrmDataStore = useLrmData();
  const searchDataStore = useSearchData();
  const searchTypeStore = useSearchType();
  const geolocationStore = useGeolocationData();

  /**
   * Call to SOLR
   */
  async function getSOLRresults(filtered = true) {
    if (geolocationStore.hasFailed) {
      return null;
    }

    const payloadSettings = {
      text: lrmDataStore.speciality,
      location: geolocationStore.geolocation,
      isLrmSearchWithPreferredDoctor:
        lrmDataStore.isLrmSearchWithPreferredDoctor,
      prefDoctor: filtered ? lrmDataStore.prefDoctorParam : null,
      hasSlot: filtered,
    };

    // handle payload of sorlR request when lrm call get an error
    if (
      lrmDataStore.isLrmSearch
      && lrmDataStore.preferredDoctorData?.error_code_sas === 'sas_pf_001'
    ) {
      payloadSettings.hasError = lrmDataStore.preferredDoctorData.error_code_sas;
    }

    const currentPaginationData = filtered
      ? searchDataStore.paginationData
      : searchDataStore.paginationDataUnfiltered;

    const searchPayload = createSearchPayload({
      filters: { ...searchDataStore.customFilters },
      paginationData: currentPaginationData,
      settings: payloadSettings,
    });

    const res = await SearchService.getSearchResults(searchPayload);

    if (filtered) {
      searchDataStore.paginationData.setPaginationData({
        ...searchDataStore.paginationData,
        ...res.infos,
        ngroups: res?.data?.grouped?.ss_field_custom_group?.ngroups || 0,
        page: parseInt(res.infos?.page, 10),
      });
    } else {
      searchDataStore.paginationDataUnfiltered.setPaginationData({
        ...searchDataStore.paginationDataUnfiltered,
        ...res.infos,
        ngroups: res?.data?.grouped?.ss_field_custom_group?.ngroups || 0,
        page: parseInt(res.infos?.page, 10),
      });
    }

    return res;
  }

  /**
   * Call sas-api & aggregator & fetch results
   * @param {*} solrArray
   * @param {*} isFiltered
   * @returns
   */
  async function getApiResults(solrArray, isFiltered = true) {
    const allPromises = [];

    // to build SAS-API payload
    const systemTimezone = dayjs.tz.guess();
    const currentDate = dayjs().utc(true);
    const startDate = `${currentDate.format(
      'YYYY-MM-DDTHH:mm:ss',
    )}${formatTimeZoneToHour(systemTimezone)}`;
    const endDate = `${dayjs(currentDate)
      .add(2, 'day')
      .format('YYYY-MM-DD')}T23:59:59${formatTimeZoneToHour(systemTimezone)}`;

    const sasApiPayload = createSasApiPayload(solrArray);
    allPromises.push(
      SearchService.getSasResults(
        sasApiPayload,
        startDate,
        endDate,
      ),
    );

    if (!searchTypeStore.isSearchStructure) {
      // to build agregator payload
      const departments = {
        county_code: geolocationStore.geolocation.countyCode || '',
        county_list: searchDataStore.paginationData?.location?.county_list || [],
      };

      const withEditorEnabled = solrArray.filter(
        (res) => !res.bs_sas_editor_disabled,
      );

      if (withEditorEnabled.length) {
        const aggregatorPayload = createAggregatorPayload(
          withEditorEnabled,
          isFiltered,
          departments,
        );

        allPromises.push(SearchService.getAggregatorResults(aggregatorPayload));
      }
    }

    const allResults = await Promise.all(allPromises);

    const sasApiRes = allResults[0] || {};
    const agregApiRes = allResults[1] || {};

    return new SearchModel(
      solrArray,
      agregApiRes || {},
      sasApiRes || {},
      {},
      {},
    ).getSearchResultsData();
  }

  return {
    getApiResults,
    getSOLRresults,
  };
};
