<?php

namespace Drupal\sas_structure\Service;

use Drupal\Core\Entity\EntityInterface;

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

  /**
   * Get Structure by finess ID.
   *
   * @param string $num_finess
   *   FINESS of structure to get.
   * @param string|null $structure_content_type
   *   Content type to filter structure search.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getStructureByFiness(
    string $num_finess,
    string $structure_content_type = NULL
  ): ?EntityInterface;

}
