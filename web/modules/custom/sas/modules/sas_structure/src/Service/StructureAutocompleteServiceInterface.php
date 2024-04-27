<?php

namespace Drupal\sas_structure\Service;

/**
 * Interface StructureAutocompleteServiceInterface.
 *
 * Service skeleton for structure autocomplete.
 *
 * @package Drupal\sas_structure\Service
 */
interface StructureAutocompleteServiceInterface {

  /**
   * Search list of structure corresponding to given search text.
   *
   * @param string $type
   *   Type of structure to search.
   * @param string $search
   *   Text to search in structure title.
   *
   * @return array
   *   List of structure corresponding to search text.
   *   Text under 3 characters will not be search and return results.
   */
  public function structureAutocomplete(string $type, string $search): array;

}
