<?php

namespace Drupal\sas_structure\Service;

use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Interface StructureSettingsHelperInterface.
 *
 * Skeleton for structure settings helper service.
 *
 * @package Drupal\sas_structure\Service
 */
interface StructureSettingsHelperInterface {

  /**
   * Get matching 'sas_structure_settings' entities filtered by criteria.
   */
  public function getSettingsBy(array $filters): array;

  /**
   * Get link to structure setting edition form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Structure node.
   *
   * @return array|null
   *   Render array of link or null.
   */
  public function getStructureSettingsLink(NodeInterface $node, UserInterface $user): ?array;

  /**
   * Get link to SOS Médecin association setting edition form.
   *
   * @param string $siret
   *   Siret of SOS Médecin association.
   * @param \Drupal\user\UserInterface $user
   *   User account.
   *
   * @return array|null
   *   Render array of link or null.
   */
  public function getSosMedecinAssociationSettingsLink(string $siret, UserInterface $user): ?array;

  /**
   * Get link to SOS Médecin association setting edition form.
   *
   * @param string $siret
   *   Siret of SOS Médecin association.
   * @param \Drupal\user\UserInterface $user
   *   User account.
   *
   * @return array|null
   *   Render string of link or null.
   */
  public function getSosMedecinSettingsUrl(string $siret, UserInterface $user): ?string;

  /**
   * Check if current user can update structure settings.
   *
   * @param \Drupal\node\NodeInterface $structure_node
   *   Structure node.
   *
   * @return bool
   *   True if user can update settings, false else.
   */
  public function checkSettingsUpdateAccess(NodeInterface $structure_node): bool;

  /**
   * Check if current user can update SOS Medecin association settings.
   *
   * @param string $siret
   *   Siret of SOS Medecin association.
   *
   * @return bool
   *   True if user can update settings, false else.
   */
  public function checkSosMedecinSettingsUpdateAccess(string $siret): bool;

}
