<?php

namespace Drupal\sas_api_client\Service;

/**
 * Interface SasAnalyticsLogServiceInterface.
 *
 * Provides service to log data to SAS ELK.
 *
 * @package Drupal\sas_api_client\Service
 */
interface SasAnalyticsLogServiceInterface {

  /**
   * Push log to ELK throw analytic endpoint.
   *
   * @param string $log_name
   *   Log name.
   * @param mixed $data
   *   Data to provide to logging system.
   */
  public function pushLog(string $log_name, mixed $data): void;

}
