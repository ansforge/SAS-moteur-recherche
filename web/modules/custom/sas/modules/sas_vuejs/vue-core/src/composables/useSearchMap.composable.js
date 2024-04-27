import { AddressService } from '@/services';
import { AddressModel } from '@/models';
import useSearchParams from './useSearchParams.composable';

export default () => {
  const { getLocationFromParams } = useSearchParams();
  let postCodeFromParams = 0;
  let position = {};
  let addressHouseNumber = null;
  let addressStreet = '';
  let addressPostCode = null;
  let addressCity = '';
  let resultAddressLabel = {};

  /**
   * Fetch coordinates of search address
   * @param {*} searchAddress
   * @returns
   */
  async function getResearchPosition(searchAddress) {
    const data = await AddressService.getAddressData(
      getLocationFromParams(searchAddress),
    );
    if (data.features) {
      Object.values(data.features).forEach((address) => {
        addressHouseNumber = address.properties.housenumber;
        addressStreet = address.properties.street;
        addressPostCode = address.properties.postcode;
        addressCity = address.properties.city;
        const numberRegex = /[-+]?[0-9]*\.?[0-9]+/g;
        const numbersFromParams = getLocationFromParams(searchAddress).match(numberRegex);
        getPostCodeFromParams(numbersFromParams);
        fetchBestResult(address.properties.score, data);
      });
    }
    return new AddressModel({
      lat: position.lat,
      lng: position.lng,
      addressLabel: resultAddressLabel,
    });
  }

  /**
   * get the post code from the full address and check if lenght is valid
   * @param {*} val
   */
  function getPostCodeFromParams(numbersFromParams) {
    if (numbersFromParams) {
      numbersFromParams.forEach((number) => {
        if (number.length && number.length === 5) {
          postCodeFromParams = number;
        }
      });
    }
  }

  /**
   * check if it is a full address and fetch the highest score result from the API
   * @param {*} score
   * @param {*} data
   */
  function fetchBestResult(score, data) {
    if (
      addressHouseNumber
      && addressStreet
      && addressPostCode
      && addressCity
      && score > 0.7
      && postCodeFromParams === addressPostCode
    ) {
      // to find && return the best result of the API in case of few result even if it is a full address
      position = {
        lat: data.features[0].geometry.coordinates[1],
        lng: data.features[0].geometry.coordinates[0],
      };
      // to get address label of best result for popup of marker
      resultAddressLabel = {
        name: data.features[0].properties.name,
        postcode: data.features[0].properties.postcode,
        city: data.features[0].properties.city,
      };
    }
  }

  return {
    getResearchPosition,
    getPostCodeFromParams,
    fetchBestResult,
  };
};
