<?php

namespace Drupal\sas_structure\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserInterface;

/**
 * Interface CptsHelperInterface.
 *
 * Specific helper skeleton for effector user account.
 *
 * @package Drupal\sas_structure\Service
 */
interface CptsHelperInterface {

  /**
   * Get list of PS for a CPTS based on FINESS numbers.
   *
   * @param array $finessNumbers
   *   Array of FINESS numbers.
   *
   * @return array List of user entities
   */
  public function getEffectorByCpts(array $finessNumbers): array;

  /**
   * Get a list of CPTS for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user for whom to retrieve CPTS.
   *
   * @return array
   *   An array of CPTS details.
   */
  public function getCptsListForUser(UserInterface $user): array;

  /**
   * Retrieves a list of NIDs for each PS associated with the specified CPTS.
   *
   * @param array $users_data
   *   Data of users (PS) associated with CPTS.
   *
   * @return array List of NIDs.
   */
  public function getNidsFromUserSettings(array $users_data): array;

  /**
   * Get Insee code of city associated to CPTS in intervention zone.
   *
   * @param \Drupal\Core\Entity\EntityInterface $structure
   *
   * @return array
   */
  public function getCptsInseeList(EntityInterface $structure): array;

  /**
   * Get CPTS phone list from associated care deals.
   *
   * @param \Drupal\Core\Entity\EntityInterface $structure
   *
   * @return array
   */
  public function getCptsCareDealPhones(EntityInterface $structure): array;

}
