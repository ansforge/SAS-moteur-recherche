/* eslint-disable no-underscore-dangle */
import { routeHelper } from '@/helpers';
import { useLrmData } from '@/stores';
import { PayloadModel } from '@/models';

/**
 * @typedef {object} PaginationData
 * @property {number} rand_id
 * @property {number} qty
 * @property {number} page
 */

export default () => {
  /**
   * Creation of the solr search payload
   * @param {object} _
   * @param {Array} _.filters - contains the ids of filters checked by user
   * @param {PaginationData} _.paginationData
   */
  const createSearchPayload = ({ filters, paginationData, settings = {} }) => {
    const payloadTemp = new PayloadModel();

    // Manage query parameters
    const lrmDataStore = useLrmData();

    const text = lrmDataStore.isLrmSearch
      ? lrmDataStore.speciality
      : settings.text || routeHelper.getUrlParam('text');

    if (text) payloadTemp.what = text;

    if (settings.prefDoctor && !settings.hasError) {
      payloadTemp.pref_doctor = settings.prefDoctor;
    }

    payloadTemp.etb = 'treat';
    payloadTemp.rand_id = parseInt(paginationData.rand_id, 10);

    if (settings.location) {
      payloadTemp.center_lat = settings.location.latitude;
      payloadTemp.center_lon = settings.location.longitude;
      payloadTemp.radius = settings.location.radius ?? settings.location.defaultRadius;
      payloadTemp.sort = settings.location.type === 'address' ? 'distance' : 'random';
    }

    payloadTemp.page = parseInt(paginationData.page, 10);
    payloadTemp.qty = paginationData.qty;
    payloadTemp.has_slot = settings.hasSlot ? 1 : 0;

    if (filters) payloadTemp.filters = filters;

    return payloadTemp.computeQuery();
  };

  /**
   * Creation of the sas api availabilities payload
   * @param {Array} results - contains the list of the results
   */
  const createSasApiPayload = (results) => {
    const sasApiPayload = [];
    results.forEach((card) => {
      sasApiPayload.push({
        nid: card.its_nid || '',
        rpps: card.ss_field_identifiant_rpps || '',
        adeli: card.ss_field_personne_adeli_num || '',
        finess: card.ss_field_identifiant_str_finess || card.ss_field_identifiant_finess || '',
        siret: card.ss_field_identif_siret || '',
        rpps_rang: card.ss_field_identifiant_active_rpps || '',
        cp: card.ss_field_codepostal || '',
        guid: card.ss_field_identifiant || '',
      });
    });
    return sasApiPayload;
  };

  /**
   * Creation of the aggregator payload
   * @param {import('@/types').ICard[]} results - contains the list of the results
   * @param {boolean} isFiltered - if the filter custom availabilty is checked
   * @param {object} departments - contains the departmenent of the search result
   */
  const createAggregatorPayload = (results, isFiltered, departments) => {
    const searchType = isFiltered ? 'filtered' : 'not filtered';
    const aggregatorPayload = [];

    aggregatorPayload.push({
      search_parameter: {
        search_zone: departments.county_code,
        county_ids: departments.county_list,
        'search-type': searchType,
      },
    });

    results.forEach((card) => {
      const currentPsInfo = {};
      if (card.its_nid) {
        const geoLocSplit = (card.locs_field_geolocalisation_latlon || '').split(',');

        currentPsInfo[card.its_nid] = {
          rpps: card.ss_field_identifiant_rpps || '',
          adeli: card.ss_field_personne_adeli_num || '',
          finess: card.ss_field_identifiant_finess || card.ss_field_identifiant_str_finess || '',
          siret: card.ss_field_identif_siret || '',
          rpps_rang: card.ss_field_identifiant_active_rpps || '',
          cp: card.ss_field_codepostal || '',
          address: card.ss_field_street,
          phone: card.tm_X3b_und_field_phone_number || card.tm_X3b_und_telephones || card.tm_X3b_und_etb_telephones || [''],
          latitude: geoLocSplit[0] || '',
          longitude: geoLocSplit[1] || '',
        };

        aggregatorPayload.push(currentPsInfo);
      }
    });

    return aggregatorPayload;
  };

  return {
    createSearchPayload,
    createSasApiPayload,
    createAggregatorPayload,
  };
};
