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
 * Class SasSolrPractitionnerQueryManager.
 *
 * @package Drupal\sas_search\Service
 */
class SasSolrPractitionerQueryManager extends SasSolrQueryBase implements SasSolrPractitionerQueryManagerInterface {

  public const MANDATORY_PARAMS = [
    'pref_doctor',
  ];

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

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
      transliteration: $transliteration);
    $this->request = $request_stack->getCurrentRequest();
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
   * Initialise solr query with base elements.
   */
  protected function initQuery(): void {

    $this->setQuery([
      sprintf('fq=index_id:%s', SasSolrQueryConstant::SOLR_INDEX),
      'lowercaseOperators=false',
      'defType=edismax',
      'sow=false',
      'ps=10',
      'wt=json',
      'json.nl=map',
      'q.op=AND',
    ]);

    $this->setQueryItem(sprintf('q=%s', $this->request->query->get('pref_doctor')));
    $this->setQueryItem('rows=20');

    // Debug mode.
    if ($this->request->get('debug') === '1') {
      $this->setQueryItem('debugQuery=on');
    }
  }

  /**
   * Set solr query field list.
   */
  protected function setFieldList(): void {
    $field_list = array_merge(SasSolrQueryConstant::FIELD_SAS_LIST, SasSolrQueryConstant::FIELD_BASE_LIST);
    $this->setQueryItem(sprintf("fl=%s", implode(',', $field_list)));
  }

  /**
   * Set filters to find preferred doctor by name or RPPS.
   */
  protected function setFilters(): void {
    // Field boosters.
    foreach (SasSolrQueryConstant::PRACTITIONER_FIELD_BOOST as $name => $boost) {
      $this->setQueryItem(sprintf('qf=%s^%s', $name, $boost));
    }

    // Phrase field boosters.
    foreach (SasSolrQueryConstant::PRACTITIONER_PHRASE_FIELD_BOOST as $name => $boost) {
      $this->setQueryItem(sprintf('pf=%s^%s', $name, $boost));
    }

    // Add filters.
    $this->setQueryItem('fq=ss_type:"professionnel_de_sante"');
  }

}
