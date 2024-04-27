<?php

namespace Drupal\sas_user_settings\Service;

/**
 * Interface SasSnpUserDataHelperInterface.
 *
 * Interface to implement sas user data helper.
 *
 * @package Drupal\sas_entity_snp_user\Service
 */
interface SasUserSettingsHelperInterface {

  public const SAS_USER_ID_DRUPAL = 1;
  public const SAS_USER_ID_RPPS_ADELI = 2;

  /**
   * Get if user have CGU in table sas_user_settings.
   *
   * @param int $user_id
   *   User ID (uid or ADELI/RPPS) used in Drupal.
   * @param int $type
   *   User type (drupal or psc).
   */
  public function updateUserCgu(int $user_id, int $type);

  /**
   * Get if user have CGU in table sas_user_settings.
   *
   * @param int $user_id
   *   User ID (uid or ADELI/RPPS) used in Drupal.
   *
   * @return string
   *   User CGU.
   */
  public function getUserCgu(int $user_id): string;

}
