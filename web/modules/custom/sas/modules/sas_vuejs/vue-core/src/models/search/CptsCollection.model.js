import SearchModel from './Search.model';

export default class CptsCollectionClass {
  constructor() {
    this.cptsCollection = [];

    /**
     * an object of { cptsFiness: boolean } which indicates
     * if the cpts card was placed or not
     */
    this.cptsToFind = {};

    /**
     * currently displayed cpts
     */
    this.currentDisplayedCpts = null;

    /**
     * intervention zone list by finess
     */
    this.cptsInterventionZones = {};
  }

  setCptsCollection = (newCollection = []) => {
    this.cptsCollection = new SearchModel(newCollection).getSearchResultsData();
    this.#setCptsTofind();
    this.#setCptsInterventionZones();
  };

  #setCptsTofind = () => {
    this.cptsToFind = this.cptsCollection.reduce((current, cpts) => {
      // eslint-disable-next-line no-param-reassign
      current[cpts.ss_field_identifiant_finess] = false;
      return current;
    }, {});
  };

  #setCptsInterventionZones = () => {
    this.cptsInterventionZones = this.cptsCollection.reduce((zones, cpts) => {
      // eslint-disable-next-line no-param-reassign
      zones[cpts.ss_field_identifiant_finess] = cpts.sm_sas_intervention_zone_insee;
      return zones;
    }, {});
  };

  setCurrentDisplayedCpts = (cptsFiness) => {
    this.currentDisplayedCpts = cptsFiness;
  };

  changeCptsToFind = (idCpts, state) => {
    if (idCpts in this.cptsToFind) {
      this.cptsToFind[idCpts] = state;
    }
  };

  resetCptsToFind = () => {
    // eslint-disable-next-line no-return-assign
    Object.keys(this.cptsToFind).forEach((key) => this.cptsToFind[key] = false);
  };
}
