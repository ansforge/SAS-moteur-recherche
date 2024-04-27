/* eslint-disable camelcase */
export default class SnpIndispoModel {
  constructor({
    vacation_mode = false,
    dates = [],
  }) {
    this.vacationMode = vacation_mode;
    this.dates = dates;
  }
}
