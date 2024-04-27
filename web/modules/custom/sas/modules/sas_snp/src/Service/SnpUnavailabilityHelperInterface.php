<?php

namespace Drupal\sas_snp\Service;

use Drupal\node\NodeInterface;

/**
 * Interface SnpUnavailabilityHelperInterface.
 *
 * Interface describing snp unavailability.
 *
 * @package Drupal\sas_snp\Service
 */
interface SnpUnavailabilityHelperInterface {

  /**
   * Get Availability for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node .
   *
   * @return bool
   *   The availability of the node.
   */
  public function isInVacationNextThreeDays(NodeInterface $node): bool;

  /**
   * Get Availability for a given node.
   *
   * @param array $node
   *   List of card.
   *
   * @return array
   *   The availability of the node.
   */
  public function getUnavalaibilities(array $node): array;

  /**
   * Returns a list of PS unavailable for the next three days.
   *
   * @return array
   */
  public function getPsNidsWithUnavailabilityInNextThreeDays(): array;

}
