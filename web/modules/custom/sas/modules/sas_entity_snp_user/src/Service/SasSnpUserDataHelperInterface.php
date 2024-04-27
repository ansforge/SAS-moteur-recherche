<?php

namespace Drupal\sas_entity_snp_user\Service;

use Drupal\sas_entity_snp_user\Entity\SasSnpUserData;

/**
 * Interface SasSnpUserDataHelperInterface.
 *
 * Interface to implement sas user data helper.
 *
 * @package Drupal\sas_entity_snp_user\Service
 */
interface SasSnpUserDataHelperInterface {

  /**
   * Get matching 'sas_user_snp_data' entities filtered by criteria.
   *
   * @param array $filters
   *   Array of filters.
   * @param bool $first
   *   Returns only one result if true.
   *
   * @return array
   *   Return matching 'sas_snp_user_data' entities.
   */
  public function getSettingsBy(array $filters, bool $first = TRUE, bool $toArray = TRUE): SasSnpUserData|array;

  /**
   * Get effector settings entity by national id (RPPS/ADELI).
   *
   * @param string $id_nat
   *   National id of the effector.
   *
   * @return \Drupal\sas_entity_snp_user\Entity\SasSnpUserData|null
   *   SasSnpUserData object representing effector settings if found.
   */
  public function getSettingsEntity(string $id_nat): ?SasSnpUserData;

  /**
   * Check if Editor slot are disabled in effector settings.
   *
   * @param $id_nat
   *   RPPS or ADELI to check.
   *
   * @return bool
   *   Return TRUE if editor slot are disabled, and FALSE else.
   */
  public function hasEditorSlotDisabled(string $id_nat): bool;

  /**
   * Check effector participation via.
   *
   * @param string $id_nat
   *   National id of the effector.
   * @param int $participation_via
   *   Participation via to check.
   *
   * @return bool
   *   Return TRUE if effector use given participation via or FALSE else.
   */
  public function hasParticipationVia(string $id_nat, int $participation_via): bool;

  /**
   * Get term ids matching establishment type.
   *
   * @param string $taxonomyType
   *   Establishment type (for example 'CDS' or 'MSP').
   *
   * @return array
   *   Array of taxonomy term ids.
   */
  public function getTermIdsByEstablishmentType(string $taxonomyType = ''): array;

}
