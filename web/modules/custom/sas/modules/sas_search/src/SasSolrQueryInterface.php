<?php

namespace Drupal\sas_search;

/**
 * Interface SasSolrQueryInterface.
 *
 * Provides structure for solr query base class.
 *
 * @package Drupal\sas_search
 */
interface SasSolrQueryInterface {

  /**
   * Check query parameter and get error if existing.
   *
   * @return string|null
   *   Error found.
   */
  public function checkQueryParameters(): ?array;

  /**
   * Get solr server URL to make select request.
   *
   * @return string
   *   Solr server select query url.
   */
  public function getSolrServerUrl(): string;

  /**
   * Get Query parameter transliterated.
   *
   * @param string $name
   *   Query parameter name.
   *
   * @return string
   *   Transliterated string.
   */
  public function getQueryParameterTransliterated(string $name): string;

  /**
   * Build solr query parameters.
   *
   * @return array
   *   List of all query parameters to pass to solr.
   */
  public function buildQuery(): array;

  /**
   * Execute Solr Query.
   *
   * @return mixed
   *   Solr Query result with JSON format.
   */
  public function executeQuery();

  /**
   * Get a formated error result.
   *
   * @param int $http_code
   *   HTTP error code to set to response.
   * @param string $error_message
   *   Message error to set to response.
   *
   * @return array
   *   JSON Formatted error response.
   */
  public function getErrorResult(int $http_code, string $error_message): array;

}
