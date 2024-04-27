<?php

namespace Drupal\sas_snp\Service;

use Drupal\node\NodeInterface;

/**
 * Interface AvailabilityBlockProviderInterface.
 *
 * Interface describing Availability Block provider service.
 *
 * @package Drupal\sas_snp\Service
 */
interface AvailabilityBlockProviderInterface {

  /**
   * Get Availability Block for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node .
   *
   * @return array|null
   *   The availability block if current user has access.
   */
  public function getAvailabilityBlock(NodeInterface $node): array|NULL;

}
