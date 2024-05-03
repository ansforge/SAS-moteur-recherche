/* eslint-disable camelcase */
export default class PayloadClass {
  computeQuery() {
    let query = '';

    query += Object.keys(this).map((key) => `${key}=${key === 'filters' ? JSON.stringify(this[key]) : this[key]}`).join('&');

    return query;
  }
}
