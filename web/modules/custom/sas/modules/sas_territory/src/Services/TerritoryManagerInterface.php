<?php

namespace Drupal\sas_territory\Services;

use Drupal\taxonomy\TermInterface;

/**
 * Interface TerritoryManagerInterface.
 *
 * Territory manager skeleton.
 *
 * @package Drupal\sas_territory\Services
 */
interface TerritoryManagerInterface {

  /**
   * Synchronize drupal term with SAS API territories.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Taxonomy term.
   * @param string $action
   *   Action to do.
   *
   * @return int|null
   *   SAS API territory ID.
   */
  public function synchronizeWithSasApi(TermInterface $term, string $action): ?int;

}
