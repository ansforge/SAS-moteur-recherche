<?php

namespace Drupal\sas_user\Service;

/**
 * Interface FileLoggerFactory.
 *
 * Skeleton for CSV file logger.
 *
 * @package Drupal\sas_user\Service
 */
interface UserFileLoggerFactoryInterface {

  /**
   * Build csv file from given data.
   *
   * @param $data
   *   Data to insert into csv file.
   *
   * @return mixed
   */
  public function buildCsvFile(array $data, string $filename);

}
