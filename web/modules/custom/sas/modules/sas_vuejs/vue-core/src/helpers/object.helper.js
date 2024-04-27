/**
 * @warning This function mutate the object passed as parameter
 * @param {object} object
 * @returns {object} A reference to the object passed as parameter without its `undefined` properties.
 * Note that it doesn't impact any sub structures other than nested objects.
 * For example, `undefined` inside array properties aren't removed
*/
// eslint-disable-next-line import/prefer-default-export
export function epurate(object) {
  for (const key in object) {
    if (object[key] === undefined) {
      // eslint-disable-next-line no-param-reassign
      delete object[key];
    } else if (typeof object[key] === 'object') {
      epurate(object[key]);
      if (!Object.keys(object[key]).length) {
        // eslint-disable-next-line no-param-reassign
        delete object[key];
      }
    }
  }
  return object;
}
