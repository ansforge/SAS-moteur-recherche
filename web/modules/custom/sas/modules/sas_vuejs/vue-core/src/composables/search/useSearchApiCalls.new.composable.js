import dayjs from 'dayjs';

import { usePayload } from '@/composables';
import {
  SAS_CPTS_EFECTOR_LIST,
} from '@/const';

import { formatTimeZoneToHour, cookie } from '@/helpers';

import { SearchModel, PayloadModel } from '@/models';

import {
  SearchService,
  SettingService,
} from '@/services';
import {
  useSearchData,
  useLrmData,
  useGeolocationData,
} from '@/stores';

// eslint-disable-next-line import/prefer-default-export
export function useSearchApiCalls() {
  const { createSearchPayload, createSasApiPayload, createAggregatorPayload } = usePayload();

  const geolocationStore = useGeolocationData();
  const lrmDataStore = useLrmData();
  const searchDataStore = useSearchData();

  /**
   * Call to SOLR - the main entrypoint of data
   * @param {object} _
   * @param {boolean} _.withSlot
   * @param {boolean} _.withPrefDoctor
   * @param {number} _.page
   * @param {number} _.searchSeed
   * @param {number} _.quantity
   * @returns {Promise<import('@/types').SolrSearchGeolocation>}
   */
  async function fetchSolrResults({
    withSlot = true,
    withPrefDoctor = false,
    page = 1,
    searchSeed,
    quantity,
  }) {
    if (geolocationStore.hasFailed) {
      return null;
    }

    const payloadSettings = {
      text: lrmDataStore.speciality,
      location: geolocationStore.geolocation,
      isLrmSearchWithPreferredDoctor:
        lrmDataStore.isLrmSearchWithPreferredDoctor,
      prefDoctor: withPrefDoctor ? lrmDataStore.prefDoctorParam : null,
      hasSlot: withSlot,
    };

    // handle payload of sorlR request when lrm call get an error
    if (
      lrmDataStore.isLrmSearch
      && lrmDataStore.preferredDoctorData?.error_code_sas === 'sas_pf_001'
    ) {
      payloadSettings.hasError = lrmDataStore.preferredDoctorData.error_code_sas;
    }

    const searchPayload = createSearchPayload({
      filters: { ...searchDataStore.customFilters },
      paginationData: {
        rand_id: searchSeed,
        qty: quantity,
        page,
      },
      settings: payloadSettings,
    });

    /** @type {import('@/types/api/Solr').SolrSearchCommon} */
    const res = await SearchService.getSearchResults(searchPayload);

    return res;
  }

  /**
   * Call to SOLR - the main entrypoint of data for CPTS
   * @param {object} _
   * @param {string} _.finess
   * @param {number} _.page
   * @param {number?} _.searchSeed
   * @param {number} _.quantity
   * @param {boolean} _.withSlot
   * @returns {Promise<import('@/types').SolrSearchGeolocation>}
   */
  async function fetchCPTSResults({
    finess, page = 1, quantity, latitude, longitude, searchSeed, withSlot = true,
  }) {
    const payload = new PayloadModel();

    if (!withSlot) {
      payload.filters = { bs_sas_overbooking: [true] };
    }

    payload.qty = quantity;
    payload.page = page;

    if (searchSeed) {
      payload.rand_id = searchSeed;
    }

    payload.finess = finess;
    payload.center_lat = latitude;
    payload.center_lon = longitude;
    payload.sort = searchSeed ? 'random' : 'distance';

    /** @type {import('@/types').SolrSearchCommon} */
    const res = await SearchService.getSearchResults(payload.computeQuery(), SAS_CPTS_EFECTOR_LIST);

    return res;
  }

  /**
   * Call sas-api & aggregator & fetch results
   * @param {object} _
   * @param {import('@/types').SolrCard[]} _.solrArray
   * @param {boolean} _.withSlot
   * @returns {Promise<import('@/types').Card[]>}
   */
  async function fetchApiResults({ solrArray, withSlot = true }) {
    if (!solrArray) return [];

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

    // to build agregator payload
    const departments = {
      county_code: geolocationStore.geolocation?.countyCode || '',
      county_list: [],
    };

    const withEditorEnabled = solrArray.filter(
      (res) => !res.bs_sas_editor_disabled,
    );

    if (!cookie.getCookie('sas_aggregator_token')) {
      await SettingService.getAggregatorToken();
    }

    if (withEditorEnabled.length) {
      const aggregatorPayload = createAggregatorPayload(
        withEditorEnabled,
        withSlot,
        departments,
      );

      allPromises.push(SearchService.getAggregatorResults(aggregatorPayload));
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
    fetchApiResults,
    fetchSolrResults,
    fetchCPTSResults,
  };
}
