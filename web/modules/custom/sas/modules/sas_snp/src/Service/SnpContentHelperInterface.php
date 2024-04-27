<?php

namespace Drupal\sas_snp\Service;

use Drupal\node\NodeInterface;

/**
 * Interface SnpContentHelperInterface.
 *
 * Interface to implements to manage helpers functions for SAS SNP contents.
 *
 * @package Drupal\sas_snp\Service
 */
interface SnpContentHelperInterface {

  /**
   * Define if an entity is snp compliant.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check.
   *
   * @return bool
   *   True if entity is available for SAS SNP.
   */
  public function isSupportSasSnpEntity(NodeInterface $node): bool;

  /**
   * Get child sas snp entity from parent santefr entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Parent node.
   *
   * @return bool|NodeInterface
   *   The sas child for santefr entity.
   */
  public function getChild(NodeInterface $node): NodeInterface|bool;

  /**
   * Get parent entity for self::SAS_ALLOWED_ENTITY.
   *
   * @return false|\Drupal\node\NodeInterface
   *   The field entity content or false.
   */
  public function getParent(NodeInterface $node): NodeInterface|FALSE;

  /**
   * Get calendar page (SAS - Plage horaire) url.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return string|null
   */
  public function getSnpContentUrl(NodeInterface $node): ?string;

  /**
   * Check access to calendar page.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return bool
   */
  public function hasSnpContentAccess(NodeInterface $node): bool;

  /**
   * Get slots of schedule in available days.
   *
   * @return false|array
   *   The list of available slot or false.
   */
  public function getSlotRefByScheduleId(int $schedule_id): array|FALSE;

}
