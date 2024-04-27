<?php

namespace Drupal\sas_geolocation\Service;

use Drupal\node\Entity\Node;

/**
 * Interface SasTimezoneHelperInterface.
 *
 * Define timezone helper service interface.
 *
 * @package Drupal\sas_geolocation\Service
 */
interface SasTimezoneHelperInterface {

  /**
   * Returns location timezone from node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   Node.
   *
   * @return string
   *   Place timezone.
   */
  public function getPlaceTimezone(Node $node): string;

}
