<?php

namespace Drupal\sas_directory_pages\Entity\Feature;

use Drupal\node\NodeInterface;

/**
 * SasSnpHelperInterface interface
 * provides getters to acces SNP slots data & context.
 */
interface SasSnpHelperInterface {

  /**
   * Check if the entity aggreg/editor data display is disabled
   * on this particular node.
   * It can only be TRUE on PS, default to FALSE on other bundles.
   *
   * @return bool|null
   *   TRUE if disabled.
   *   FALSE if not disabled.
   *   null if we could not determine.
   */
  public function isEditorDisplayDisabled(): bool|NULL;

  /**
   * Check if the entity aggreg/editor data display is disabled
   * on the page of this particular node depending on its places
   * It can only be TRUE on PS, default to FALSE on other bundles.
   *
   * @return bool|null
   *   TRUE if disabled.
   *   FALSE if not disabled.
   *   null if we could not determine.
   */
  public function isEditorDisplayDisabledOnPage(): bool|NULL;

  /**
   * Get the sas_time_slots referencing the current node.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  public function getSasTimeSlots(): NodeInterface|NULL;

  /**
   * Get the schedule id of the entity from its sas_time_slots node if exists.
   *
   * @return int|null
   *   The schedule id identifying the entity in the SAS API.
   *   null if the sas_time_slots node does not exist or schedule_id is empty
   */
  public function getScheduleId(): int|NULL;

  /**
   * Get the info edit of the entity from its sas_time_slots node if exists.
   *
   * @return string|null
   */
  public function getInfoEdit(): string|NULL;

  /**
   * Get place main data.
   *
   * @return array
   *   Main data as array.
   */
  public function getPlaceData(): array;

  /**
   * Get place ids like RPPS/ADELI for effector or FINESS/SIRET for organisation.
   *
   * @return array
   *   List of ids found for this place.
   */
  public function getPlaceIds(): array;

}
