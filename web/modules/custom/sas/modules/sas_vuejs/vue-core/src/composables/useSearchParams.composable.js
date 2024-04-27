import { ref } from 'vue';
import { routeHelper } from '@/helpers';

export default () => {
  const urlParams = ref({});

  function getUrlParams() {
    const searchParams = new URLSearchParams(window.location.search);

    /* eslint-disable no-restricted-syntax */
    for (const [key, value] of searchParams.entries()) {
      urlParams.value[key] = value;
    }
  }

  // on app init get all url params
  getUrlParams();

  /**
   * get location text from URL params
   * @param {*} addressFromLrm
   * @returns
   */
  const getLocationFromParams = (addressFromLrm) => (addressFromLrm || routeHelper.getUrlParam('loc'));

  return {
    urlParams,
    getLocationFromParams,
  };
};
