<?php

declare(strict_types = 1);

namespace Drupal\sas_search\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\sas_search\Enum\SasSolrQueryConstant;
use Drupal\sas_search\SasSolrQueryBase;
use Drupal\sas_snp\Enum\SnpConstant;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solr query builder for CPTS effector list.
 */
final class SasSolrCptsEffectorQueryManager extends SasSolrQueryBase {

  /**
   * @const List of mandatory query parameters in CPTS effector search endpoint.
   */
  public const MANDATORY_PARAMS = [
    'finess',
    'sort',
  ];

  public const MANDATORY_CONDITIONNAL_PARAMS = [
    SasSolrQueryConstant::SEARCH_SORT_DISTANCE => [
      'center_lat',
      'center_lon',
    ],
    SasSolrQueryConstant::SEARCH_SORT_RANDOM => [
      'rand_id',
    ],
  ];

  /**
   * SasSolrPractitionerQueryManager constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http Client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request service.
   */
  public function __construct(
    Client $http_client,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    TransliterationInterface $transliteration
  ) {
    parent::__construct(
      http_client: $http_client,
      config_factory: $config_factory,
      cache: $cache,
      logger_factory: $logger_factory,
      transliteration: $transliteration,
      request_stack: $request_stack
    );
  }

  public function checkQueryParameters(): ?array {
    // Check mandatory fields.
    $mandatory_error = parent::checkQueryParameters();
    if (!empty($mandatory_error)) {
      return $mandatory_error;
    }

    // Check conditional mandatory fields.
    foreach (self::MANDATORY_CONDITIONNAL_PARAMS[$this->request->query->get('sort')] as $mandatory_param) {
      if (empty($this->request->get($mandatory_param))) {
        return $this->getErrorResult(
          Response::HTTP_BAD_REQUEST,
          sprintf('Parameter "%s" is missing.', $mandatory_param)
        );
      }
    }

    return NULL;
  }

  public function buildQuery(): array {
    $this->initQuery();
    $this->setFieldList();
    $this->setFilters();
    $this->setSort();
    $this->getVacationFilter();

    return $this->query;
  }

  /**
   * Initialise solr query Cpts with base elements.
   */
  private function initQuery(): void {

    $this->setQuery(
      SasSolrQueryConstant::BASE_QUERY_PARAMETERS
    );
    $this->setQueryItem(
      sprintf('fq=index_id:%s', SasSolrQueryConstant::SOLR_INDEX)
    );
    $this->setQueryItem('q=*:*');

    // Manage pagination.
    $pagination = $this->getPagination();
    $this->setQueryItem(sprintf('start=%d', $pagination['offset']));
    $this->setQueryItem(sprintf('rows=%d', $pagination['item_per_page']));
  }

  /**
   * Set solr query field list.
   */
  private function setFieldList(): void {
    $field_list = array_merge(SasSolrQueryConstant::FIELD_SAS_LIST, SasSolrQueryConstant::FIELD_BASE_LIST);
    $this->setQueryItem(sprintf("fl=%s", implode(',', $field_list)));
  }

  /**
   * Set filters to find preferred doctor by name or RPPS.
   */
  protected function setFilters(): void {
    $this->setQueryItem(
      sprintf('fq=ss_type:%s', SnpConstant::SAS_PROFESSIONAL_CT)
    );

    $this->setQueryItem(
      sprintf('fq=ss_sas_cpts_finess:%s', $this->request->query->get('finess'))
    );
  }

  /**
   * Get Geolocation filter and sort based on geolocation data passed in url.
   */
  protected function setSort(): void {

    $center_lat = $this->request->get('center_lat') ?? '';
    $center_lon = $this->request->get('center_lon') ?? '';
    $sort = $this->request->get('sort') ?? '';

    // Define result sort.
    switch ($sort) {
      case SasSolrQueryConstant::SEARCH_SORT_DISTANCE:
        $this->setQueryItem(sprintf('pt=%s,%s', $center_lat, $center_lon));
        $this->setQueryItem('sfield=locs_field_geolocalisation_latlon');
        $this->setQueryItem(sprintf(
          'sort=bs_sas_participation desc, geodist(locs_field_geolocalisation_latlon,%s,%s) asc',
          $center_lat,
          $center_lon
        ));
        break;

      case SasSolrQueryConstant::SEARCH_SORT_RANDOM:
        $this->setQueryItem(sprintf(
          'sort=bs_sas_participation desc, random_%s desc', $this->request->get('rand_id')
        ));
        break;

      default:
        break;
    }
  }

}
