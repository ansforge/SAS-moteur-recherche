<?php

namespace Drupal\sas_snp\Service;

/**
 * Interface SearchResultsFormatterInterface.
 *
 * Interface to implement search results formatter.
 *
 * @package Drupal\sas_snp\Service
 */
interface SnpSlotsFormatterInterface {

  /**
   * Apply specific treatments on slots returned by SAS-API, like :
   *  - order slots by their start date
   *  - add timestamp property for each $results entry, corresponding to a
   *    practitioner or a card. The goal here is to be able to order cards in
   *    search results page by the nearest opened slot.
   *
   * @param array $results
   *   SAS-API slots results.
   *
   * @return array
   *   Return slots in specific format.
   */
  public function orderByTimestamp(array $results): array;

}
