<?php

namespace Drupal\sas_search;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\sas_search\Enum\SasSolrQueryConstant;
use Drupal\sas_snp\Enum\SnpConstant;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SasSolrQueryBase.
 *
 * Provide base service to make query on Solr server.
 *
 * @package Drupal\sas_search
 */
abstract class SasSolrQueryBase implements SasSolrQueryInterface {

  public const MANDATORY_PARAMS = [];

  /**
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected TransliterationInterface $transliteration;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * Solr query parameters.
   *
   * @var array
   */
  protected array $query = [];

  /**
   * Pagination data.
   *
   * @var array
   */
  protected array $pagination;

  public function __construct(
    Client $http_client,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory,
    TransliterationInterface $transliteration,
    RequestStack $request_stack
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('sas_search');
    $this->transliteration = $transliteration;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritDoc}
   */
  public function checkQueryParameters(): ?array {

    foreach (static::MANDATORY_PARAMS as $name) {
      if (empty($this->request->get($name))) {
        return $this->getErrorResult(
          Response::HTTP_BAD_REQUEST,
          sprintf('Parameter "%s" is missing.', $name)
        );
      }
    }

    return NULL;
  }

  /**
   * @return array
   */
  public function getQuery(): array {
    return $this->query;
  }

  /**
   * @param array $query
   */
  public function setQuery(array $query): void {
    $this->query = $query;
  }

  /**
   * @param string $value
   */
  public function setQueryItem(string $value): void {
    $this->query[] = $value;
  }

  /**
   * {@inheritDoc}
   */
  public function getPagination() {
    if (!empty($this->pagination)) {
      return $this->pagination;
    }

    $item_per_page = $this->request->get('qty') ?? SasSolrQueryConstant::DEFAULT_ITEM_PER_PAGE;
    $page = $this->request->get('page') ?? SasSolrQueryConstant::DEFAULT_PAGE;
    $offset = ($page - 1) * $item_per_page;

    $pagination = [
      'page' => $page,
      'item_per_page' => $item_per_page,
      'offset' => $offset,
    ];

    $this->pagination = $pagination;
    return $this->pagination;
  }

  /**
   * Get Vacation filters to remove items that are not available.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  protected function getVacationFilter(): void {
    $this->setQueryItem('fq=-(bs_sas_vacation:"true")');

    $time_start = new DrupalDateTime('now');
    $time_start = $time_start->getTimestamp();
    $time_end = (new DrupalDateTime('now'))->add(new \DateInterval('P2D'));
    $time_end = $time_end->getTimestamp();
    for ($i = 0; $i < SnpConstant::SAS_MAX_VACATION_SLOT_NB; $i++) {
      $this->setQueryItem(sprintf(
        'fq=-(its_sas_vacation_slot_start_%d:[* TO "%s"] AND its_sas_vacation_slot_end_%d:["%s" TO *])',
        $i, $time_start, $i, $time_end
      ));
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getSolrServerUrl(): string {
    $solr_url = $this->cache->get('sas:solr_url');

    if (!empty($solr_url) && !empty($solr_url->data)) {
      return $solr_url->data;
    }

    $config = $this->configFactory->get("search_api.server.core_healthoffer");
    $backend_conf = $config->get('backend_config');
    $connector_config = $backend_conf['connector_config'];

    // Creates a base url on which we will add subsequent parameters.
    $solr_url = sprintf(
      '%s://%s:%s%ssolr/%s/select',
      $connector_config['scheme'],
      $connector_config['host'],
      $connector_config['port'],
      $connector_config['path'],
      $connector_config['core']
    );
    $this->cache->set('sas:solr_url', $solr_url);

    return $solr_url;
  }

  /**
   * {@inheritDoc}
   */
  public function getQueryParameterTransliterated(string $name): string {
    if (empty($this->request->get($name)) || !is_string($this->request->get($name))) {
      return '';
    }

    return strtolower(
      $this->transliteration->transliterate(
        $this->request->get($name),
        LanguageInterface::LANGCODE_DEFAULT,
        '_'
      )
    );
  }

  /**
   * {@inheritDoc}
   */
  public function executeQuery() {

    $curlOptions = [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_SSL_VERIFYPEER => FALSE,
    ];

    $requestOptions = [
      'curl' => $curlOptions,
      'headers' => [
        'Content-type' => 'application/x-www-form-urlencoded',
        'Accept' => 'application/json',
      ],
      'timeout' => 60,
      'body' => implode('&', $this->query),
    ];

    try {
      $result = $this->httpClient->post(
        $this->getSolrServerUrl(),
        $requestOptions
      );

      if (!preg_match('/^2(\d{2})$/', $result->getStatusCode())) {
        $error = sprintf('SAS Api WS Error - HTTP Code %d : %s',
          $result->getStatusCode() ?? 0,
          $result->getReasonPhrase()
        );
        $this->logger->error($error);

        return $this->getErrorResult($result->getStatusCode(), $result->getReasonPhrase());
      }

      return $result->getBody()->getContents();
    }
    catch (GuzzleException $e) {
      $error = sprintf('SAS Api WS Error - HTTP Code %s : %s',
        $e->getCode(),
        $e->getMessage()
      );
      $this->logger->error($error);

      return $this->getErrorResult($e->getCode(), $e->getMessage());
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getErrorResult(int $http_code, string $error_message): array {
    return [
      'error_code' => $http_code,
      'error_message' => $error_message,
    ];
  }

}
