<?php

namespace Drupal\sas_directory_pages\Service;

/**
 * Interface SnpContentHelperInterface.
 *
 * Interface to implements to manage helpers functions for payload orientation.
 *
 * @package Drupal\sas_directory_pages\Service
 */
interface PayloadOrientationHelperInterface {

  /**
   * @param $node
   *   Node object.
   *
   * @return array
   *   Return Payload
   */
  public function payload($node): array;

}
