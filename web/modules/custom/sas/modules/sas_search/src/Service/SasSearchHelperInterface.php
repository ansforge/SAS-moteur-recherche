<?php

namespace Drupal\sas_search\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface SasSuggestedSearchHelperInterface.
 *
 * Provides structure for class giving suggested search data.
 *
 * @package Drupal\sas_search\Service
 */
interface SasSearchHelperInterface {

  /**
   * Get list of search suggestions.
   *
   * @return array
   *   List of suggestion.
   *
   *    Each suggestion is an array with :
   *      - title : suggestion title to display
   *      - filters: list of term name to filter split by solr property name.
   */
  public function getSearchSuggestions(): array;

  /**
   * Get suggested search for given search text.
   *
   * @param string $search
   *   Search text.
   *
   * @return array
   *   If found return suggestion array with shape :
   *      - title : suggestion title to display
   *      - filters: list of term name to filter split by solr property name.
   */
  public function getSuggestedSearch(string $search): array;

  /**
   * Get current day.
   *
   * @return string
   *   Returns the current day in letters.
   */
  public function getCurrentDay();

  /**
   * New location.
   *
   * @return string
   *   Returns new location.
   */
  public function newLocation(Request $request): string;

  /**
   * Check if origin is valid and active from aggregator.
   *
   * @param string $origin
   *   Origin of the query.
   *
   * @return bool
   *   TRUE if origin found and active on aggregator, FALSE else.
   */
  public function isValidOrigin(string $origin): bool;

}
