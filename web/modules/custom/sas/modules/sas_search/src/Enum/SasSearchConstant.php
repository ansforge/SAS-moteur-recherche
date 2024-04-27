<?php

namespace Drupal\sas_search\Enum;

/**
 * Class SasSearchConstant.
 *
 * Provides global sas search constants.
 *
 * @package Drupal\sas_search\Enum
 */
final class SasSearchConstant {

  /**
   * Vocabulary corresponding to health professional.
   */
  const SUGGESTION_PRO_VOCABULARY = [
    'cnam_professions',
  ];

  /**
   * SOLR property name containing profession name.
   */
  const SUGGESTIONS_PROFESSIONAL_SOLR_FIELD = 'tm_X3b_und_field_profession_name';

  /**
   * SOLR property name containing establishment type names (CDS, MSP, ...).
   */
  const SUGGESTIONS_STRUCTURE_SOLR_FIELD = 'tm_X3b_und_establishment_type_names';

  const LRM_LOCATION = [
    'inseecode' => 'inseecode',
    'city' => 'city',
    'streetname' => 'streetname',
    'streetnumber' => 'streetnumber',
  ];

}
