<?php

namespace Drupal\sas_structure\Service;

/**
 * Interface FinessStructureHelperInterface.
 *
 * Helper skeleton for finess structures.
 *
 * @package Drupal\sas_structure\Service
 */
interface FinessStructureHelperInterface {

  /**
   * Search list of structure corresponding to given search text.
   *
   * @param string $type
   *   Type of structure to search.
   * @param string $text
   *   Text to search in structure title.
   *
   * @return array
   *   List of structure corresponding to search text.
   */
  public function searchStructures(string $type, string $text): array;

}
