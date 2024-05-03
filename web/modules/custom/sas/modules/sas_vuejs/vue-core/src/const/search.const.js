import { SAS_SEARCH_OVERBOOKING_FILTER_LABEL } from './filters.const';

export const SAS_GEOLOCATION_HAS_FAILED = (value) => (`Le moteur de recherche SAS n’a pas identifié de localisation pour l’adresse: "${value}". Pour corriger la localisation, vous pouvez renseigner une nouvelle adresse dans le champ "Renseigner une adresse" ou identifier la zone de recherche sur la carte et cliquer sur "Relancer la recherche"`);
export const SAS_NO_HEALTH_OFFER_IN_ZONE_SENTENCE = 'Aucune offre de soins n’est disponible dans les 48h sur la zone/adresse recherchée.';
export const SAS_NO_OVERBOOKING_HEALTH_OFFER_IN_ZONE_SENTENCE = 'Aucune offre de soins n’a accepté d’être contactée par la régulation médicale pour prendre en charge des patients en sus des disponibilités dans la zone/adresse recherchée.';
export const SAS_RELAUNCH_IN_OVERBOOKING_MODE_SENTENCE = `Veuillez lancer la recherche "${SAS_SEARCH_OVERBOOKING_FILTER_LABEL}" pour identifier l’offre de soins acceptant d'être contactée par la régulation médicale pour prendre en charge des patients en sus des disponibilités.`;

/**
 * Used by the CollectionBaker family of composable
 */
export const BAKING_STATUS = Object.freeze({
  NOT_OPEN_YET: 'not-open',
  FETCHING_SOLR: 'fetching-solr',
  FETCHING_APIS: 'fetching-apis',
  PENDING: 'pending-for-instructions',
  CLOSED: 'closed',
});

export const SEARCH_STATUS = Object.freeze({
  /**
   * The search hasn't been initialized yet
   */
  NOT_STARTED: 'not-started',

  /**
   * The baking of cards is happening
   */
  LOADING: 'loading',

  /**
   * In the current context (pagination + current deck), we don't need to fetch more cards
   */
  HAS_ENOUGH: 'has-enough',

  /**
   * No more solr result to query from
   */
  FINISHED: 'finished',
});
