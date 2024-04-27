<?php

namespace Drupal\sas_user\Plugin\rest\resource;

use Drupal\sas_core\Plugin\SasResourceBase;

/**
 * Class SasUserDataResourceBase.
 *
 * Base resource plugin to manage user data endpoint.
 *
 * @package Drupal\sas_user\Plugin\rest\resource
 */
abstract class SasUserDataResourceBase extends SasResourceBase {

  /**
   * {@inheritDoc}
   */
  protected function getBaseRouteRequirements($method): array {
    $requirements = parent::getBaseRouteRequirements($method);
    $requirements['_sas_user_data_access_check'] = 'TRUE';
    return $requirements;
  }

}
