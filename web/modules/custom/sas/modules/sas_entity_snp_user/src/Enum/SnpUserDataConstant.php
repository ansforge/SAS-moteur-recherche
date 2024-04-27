<?php
// phpcs:ignoreFile -- PHP_CS throw an exception on JetBrains namespaces.

namespace Drupal\sas_entity_snp_user\Enum;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class SnpUserDataConstant.
 *
 * Provides constants relatives to SAS SNP user data.
 *
 * @package Drupal\sas_entity_snp_user\Enum
 */
final class SnpUserDataConstant {

  /**
   * Column name inside "sas_snp_user_data" table to store if a user wants to
   * have its editor slots or not.
   */
  const FEATURE_EDITOR = 'editor_disabled';

  /**
   * Constant to check if a user accept overbooking or not.
   */
  const FEATURE_OVERBOOKING = 'accept_overbooking';

  /**
   * Constant to check if a user is participating to emergency reorientation
   * or not.
   */
  const FEATURE_EMERGENCY_REORIENTATION = 'emergency_reorientation';

  /**
   * User types : Drupal or ProSanté Connect (PSC) user.
   */
  const SAS_USER_TYPE_DRUPAL = 1;
  const SAS_USER_TYPE_PSC = 2;

  /**
   * Field values to store inside "participation_sas_via" field of
   * "sas_snp_user_data" table.
   */
  const SAS_PARTICIPATION_MY_OFFICE = 1;
  const SAS_PARTICIPATION_MY_CPTS = 2;
  const SAS_PARTICIPATION_MY_MSP = 3;
  const SAS_PARTICIPATION_MY_SOS_MEDECIN = 4;

  const PARTICIPATION_VIA_NOT_INDEXED = [
    self::SAS_PARTICIPATION_MY_MSP,
    self::SAS_PARTICIPATION_MY_SOS_MEDECIN,
  ];

  /**
   * Get Entity fields for each feature.
   *
   * @return string[]
   */
  #[ArrayShape([
    self::FEATURE_EDITOR => "string",
    self::FEATURE_OVERBOOKING => "string",
    self::FEATURE_EMERGENCY_REORIENTATION => "string",
  ])]
  public static function getFeatureEntityFields() {
    return [
      self::FEATURE_EDITOR => 'editor_disabled',
      self::FEATURE_OVERBOOKING => 'participation_sas',
      self::FEATURE_EMERGENCY_REORIENTATION => 'forfait_reo_enabled',
    ];
  }

}
