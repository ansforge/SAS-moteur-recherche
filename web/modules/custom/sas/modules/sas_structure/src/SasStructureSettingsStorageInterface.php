<?php

namespace Drupal\sas_structure;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\sas_structure\Entity\SasStructureSettings;

/**
 * Interface SasStructureSettingsStorageInterface.
 *
 * Interface defining storage structure for SasStructureSettings entity.
 *
 * @package Drupal\sas_structure
 */
interface SasStructureSettingsStorageInterface extends ContentEntityStorageInterface {

  /**
   * Get SasStructureSettings by its FINESS.
   *
   * @param string $structure_id
   *   Structure Id (FINESS, SIRET).
   *
   * @return \Drupal\sas_structure\Entity\SasStructureSettings|null
   *   Return found entity if exists, null else.
   */
  public function loadByStructureId(string $structure_id): ?SasStructureSettings;

}
