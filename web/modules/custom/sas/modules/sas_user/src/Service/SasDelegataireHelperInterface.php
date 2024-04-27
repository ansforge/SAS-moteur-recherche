<?php

namespace Drupal\sas_user\Service;

/**
 * Interface SasDelegataireHelperInterface.
 *
 * Specific helper skeleton for "SAS - Délégataire" user accounts.
 *
 * @package Drupal\sas_user\Service
 */
interface SasDelegataireHelperInterface {

  /**
   * Get all RPPS or ADELI of user account with "SAS - Effecteur" role.
   *
   * @param int $user_id
   *   Drupal account id.
   *
   * @return array
   *   List of user account RPPS or ADELI with "SAS - Effecteur" role.
   */
  public function getEffectorDelegations(int $user_id): array;

}
