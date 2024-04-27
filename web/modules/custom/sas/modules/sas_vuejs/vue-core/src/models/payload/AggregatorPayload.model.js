import AggregatorPractitioner from './AggregatorPractitioner.model';

export default class AggregatorPayload {
  constructor() {
    this.searchZone = '';
    this.searchCounties = [];
    this.searchType = 'filtered';
    this.practitionerCards = [];
  }

  /* eslint-disable no-underscore-dangle */
  /**
   * @param {String} zone
   */
  set _searchZone(zone) {
    this.searchZone = zone ?? '';
  }

  /**
   * @param {Array} counties
   */
  set _searchCounties(counties) {
    this.searchCounties = counties ?? [];
  }

  /**
   * @param {String} type
   */
  set _searchType(type) {
    this.searchType = type ?? '';
  }

  /**
   * @param {Array} practioners
   */
  set _practitionerCards(practioners = []) {
    this.practitionerCards = practioners?.map((ps) => new AggregatorPractitioner(ps)) ?? [];
  }
  /* eslint-enable no-underscore-dangle */
}
