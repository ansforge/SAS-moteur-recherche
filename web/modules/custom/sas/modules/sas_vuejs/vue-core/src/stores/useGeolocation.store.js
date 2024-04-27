import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/**
 * The store responsible of the current location of the search.
 * It exposes a geolocation object and some computed properties built from it for more clarity and ease of use
 */
/* eslint-disable import/prefer-default-export */
export const useGeolocationData = defineStore('geolocation', () => {
  const GEOLOCATION_TYPE = Object.freeze({
    ADDRESS: 'address',
    CITY: 'city',
    COUNTY: 'county',
  });

  /**
   * It is either given by the backend on initial loading of page
   * or set by hand with less information when relaunching the research via the map
   * @type {import('vue').Ref<import('@/types/api/Primitives').Location | undefined>}
   */
  const geolocation = ref(window.API.location);

  const hasFailed = computed(() => (!geolocation.value));

  const streetLabel = computed(() => {
    if (hasFailed.value) return '';

    return `${geolocation.value.houseNumber || ''} ${geolocation.value.street || ''}`.trim();
  });

  const cityLabel = computed(() => {
    if (hasFailed.value) return '';

    return `${geolocation.value.postCode || ''} ${geolocation.value.city || ''}`.trim();
  });

  /**
   * Prefer this property to `geolocation.fullAddress` as it does extra work to handle all use cases.
   */
  const fullAddress = computed(() => {
    /**
     * If geolocation has failed, we still want to display the address that caused it to fail.
     * It is computed by the backend based on the query parameters of the url.
     */
    if (hasFailed.value) return window.API?.location_input;

    switch (geolocation.value.type) {
      case GEOLOCATION_TYPE.ADDRESS: return `${streetLabel.value}, ${cityLabel.value}`;
      case GEOLOCATION_TYPE.CITY: return cityLabel.value;
      case GEOLOCATION_TYPE.COUNTY: return geolocation.value.countyName || '';
      default: return '';
    }
  });

  return {
    cityLabel,
    fullAddress,
    geolocation,
    GEOLOCATION_TYPE,
    hasFailed,
    streetLabel,
  };
});
