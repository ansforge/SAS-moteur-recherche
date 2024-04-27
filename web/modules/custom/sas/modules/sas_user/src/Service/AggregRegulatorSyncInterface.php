<?php

namespace Drupal\sas_user\Service;

use Drupal\user\UserInterface;

/**
 * Interface SasRegulatorSyncInterface.
 *
 * Service pattern to manage regulator synchronisation.
 *
 * @package Drupal\sas_user\Service
 */
interface AggregRegulatorSyncInterface {

  /**
   * Build payload data to send into regulator synchronisation endpoints.
   *
   * @param \Drupal\user\UserInterface $user
   *   Drupal user object.
   * @param bool $habilitation
   *   Habilitation.
   *
   * @return array
   *   Data to send into endpoint request.
   */
  public function buildRegulatorPayload(UserInterface $user, bool $habilitation = TRUE, string $old_email = NULL): array;

  /**
   * Make synchronisation of regulator on aggregator.
   *
   * @param string $endpoint_name
   *   Endpoint plugin name to use to make synchronisation.
   * @param array $regulator_data
   *   Regulator data to send.
   *
   * @return mixed
   *   Synchronisation response.
   */
  public function makeRegulatorSync(string $endpoint_name, array $regulator_data): mixed;

}
