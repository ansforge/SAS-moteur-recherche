<?php

declare(strict_types = 1);

namespace Drupal\sas_user\Service;

use Drupal\user\UserInterface;

/**
 * Class SasStructureManagerHelper.
 *
 * Specific helper for Structure manager user account.
 *
 * @package Drupal\sas_user\Service
 */
interface SasStructureManagerHelperInterface {

  /**
   * Get list of place linked to all cpts associated to the given structure
   * manager account.
   *
   * @param \Drupal\user\UserInterface $user
   *   Structure manager user account.
   *
   * @return array
   *   List of place nid.
   */
  public function getCptsPlaceNids(UserInterface $user): array;

  /**
   * Checks if a user is a manager for SOS Doctor, CPTS, or CDS.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object to check.
   * @param string $structure
   *   The constant that defines the name of the field to check.
   *
   * @return bool
   *   Returns TRUE if the user is a structure manager.
   */
  public function isStructureManager(UserInterface $user, string $structure): bool;

}
