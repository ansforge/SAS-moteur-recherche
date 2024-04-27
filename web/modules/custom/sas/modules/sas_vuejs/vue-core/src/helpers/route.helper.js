export default {
  /**
   *
   * @param {string} paramName
   * @returns The value of the parameter or `''` if it doesn't exist
   */
  getUrlParam(paramName) {
    const route = new URLSearchParams(window.location.search);

    return route.get(paramName) ?? '';
  },
};
