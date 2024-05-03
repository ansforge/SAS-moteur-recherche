<?php

namespace Drupal\sas_search\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\sas_search\Enum\SasSolrQueryConstant;
use Drupal\sas_search\SasSolrQueryBase;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SasSolrCptsQueryManager
 * Sas search cpts Manager to build and execute query.
 *
 * @package Drupal\sas_search\Service
 *
 * @SuppressWarnings("CPD-START")
 */
class SasSolrCptsQueryManager extends SasSolrQueryBase implements SasSolrCptsQueryManagerInterface {

  public const MANDATORY_PARAMS = [
    'code_insee',
  ];

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * SasSolrCptsQueryManager constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Http Client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Caches service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
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

  /**
   * {@inheritDoc}
   */
  public function buildQuery(): array {
    $this->initQuery();
    $this->setFieldList();
    $this->setFilters();

    return $this->query;
  }

  /**
   * Initialise solr query Cpts with base elements.
   */
  protected function initQuery(): void {

    $this->setQuery([
      sprintf('fq=index_id:%s', SasSolrQueryConstant::SOLR_INDEX),
      SasSolrQueryConstant::BASE_QUERY_PARAMETERS,
    ]);

    $this->setQueryItem('q=*:*');
    $this->setQueryItem('rows=50');
  }

  /**
   * Set solr query cpts list field.
   */
  protected function setFieldList(): void {
    $field_list = array_merge(
      SasSolrQueryConstant::FIELD_SAS_LIST,
      SasSolrQueryConstant::FIELD_BASE_LIST
    );
    $this->setQueryItem(sprintf("fl=%s", implode(',', $field_list)));
  }

  /**
   * Set filters to find cpts code by insee code.
   */
  protected function setFilters(): void {
    $codeInsee = $this->request->get('code_insee');
    // Add filters.
    $this->setQueryItem('fq=ss_type:"health_institution"');
    $this->setQueryItem(
      'fq=tm_X3b_und_establishment_type_names:"Communauté Professionnelle Territoriale de Santé (CPTS)"'
    );
    $this->setQueryItem(
      sprintf('fq=sm_sas_intervention_zone_insee:%s', $codeInsee)
    );
  }

}
