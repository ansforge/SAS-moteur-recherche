<?php

namespace Drupal\sas_search;

/**
 * Trait SolrDataFormatterTrait.
 *
 * Provides methods to format solr search and index data.
 *
 * @package Drupal\sas_search
 */
trait SolrDataFormatterTrait {

  /**
   * Sort and format speciality ids as a string with comma separator.
   *
   * @param array $ids
   *   List of ids to format.
   *
   * @return string
   *   String formatted list.
   */
  public function formatSpecialityIdsAsString(array $ids): string {
    asort(array: $ids, flags: SORT_NUMERIC);
    return implode(separator: ',', array: $ids);
  }

}
