<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\Session\AccountProxy;
use Drupal\user\UserInterface;

/**
 * Interface SasEffectorHelperInterface.
 *
 * Specific helper skeleton for effector user account.
 *
 * @package Drupal\sas_user\Service
 */
interface SasEffectorHelperInterface {

  /**
   * Get all effectors user account.
   *
   * @return array|null
   *   List of user account with SAS - Effecteur role.
   */
  public function getAllEffectors(): ?array;

  /**
   * Get list of RPPS/ADELI corresponding to users professional sheet.
   *
   * DO NOT USE this method, created only for RPPS/ADELI migration.
   * Will be remove after migration.
   *
   * TO REMOVE AFTER MIGRATION
   *
   * @return array|null
   *   List of RPPS and ADELI based on professional sheet set in
   *   "SAS - Fiche professionnel de santé" field.
   */
  public function getEffectorRppsAdeliBySheets(UserInterface $user): ?array;

  /**
   * Get node for numero adeli/rpps.
   *
   * @param string $rpps_adeli_num
   *   Numéro rpps/adeli.
   * @param string $prefix
   *   RPPS/ADELI prefix to check.
   *
   * @return array
   */
  public function getContentByRppsAdeli(string $rpps_adeli_num, string $prefix): array;

  /**
   * Get node ids corresponding to a list of Activity RPPS.
   *
   * @param array $activity_rpps_list
   *
   * @return array
   */
  public function getActivityRppsNids(array $activity_rpps_list): array;

  /**
   * Get addresses list for given effector identified by its national id.
   *
   * @param string $id_nat
   *   National ID (RPPS or ADELI).
   * @param bool $cpts_only
   *   Filter addresses to get only them witch are linked to a CPTS.
   *
   * @return array
   *   List of addresses and main data corresponding.
   */
  public function getAddresses(string $id_nat, bool $cpts_only = FALSE): array;

  /**
   * Check if content exists with given RPPS or ADELI.
   *
   * @param string $rpps_adeli_num
   *   RPPS/ADELI id to check.
   * @param string $prefix
   *   RPPS/ADELI prefix to check.
   *
   * @return bool
   *   TRUE if at least one content is found, FALSE else.
   */
  public function isExistingContentByRppsAdeli(string $rpps_adeli_num, string $prefix): bool;

  /**
   * Check if adeli/rpps is already use by a user account.
   *
   * @param string $rpps_adeli_num
   *   Numéro rpps/adeli.
   *
   * @return bool
   *   TRUE if user found with this RPPS/ADELI, FALSE else.
   */
  public function userRppsAdeliExists(string $rpps_adeli_num): bool;

  /**
   * Split effector national id (RPPS/ADELI) to get prefix and id separately.
   *
   * @param string $rpps_adeli_num
   *   National Id to split.
   *
   * @return array
   *   Array containing :
   *    - prefix (0 or 8)
   *    - id
   */
  public function getEffectorIdParts(string $rpps_adeli_num): array;

  /**
   * Get (RPPS/ADELI) in th uid.
   *
   * @param string $uid
   *   Id user.
   *
   * @return string
   *   RPPS ADELI
   */
  public function getRppsAdeliInUserId(string $uid): string;

  /**
   * Check if adeli/rpps is already use by sas_snp_user_data.
   *
   * @param string $rpps_adeli_num
   *   Numéro rpps/adeli.
   *
   * @return bool
   *   TRUE if user id found with this RPPS/ADELI, FALSE else.
   */
  public function isUserIdSettingsExists(string $rpps_adeli_num): bool;

  /**
   * Get current Drupal or PSC user national ID (RPPS or ADELI).
   *
   * @return int|null
   *   Returns current user RPPS or ADELI.
   */
  public function getCurrentUserNationalId(): ?int;

  /**
   * Get the current user.
   *
   * @return \Drupal\Core\Session\AccountProxy
   */
  public function getCurrentUser(): AccountProxy;

  /**
   * Loads a user's professional sheet based on the provided user ID.
   *
   * @param mixed $user_id
   *   The user ID.
   *
   * @return mixed
   *   Returns the corresponding node representing the PS sheet if found
   */
  public function getProfessionalSheetByUserId(mixed $user_id);

}
