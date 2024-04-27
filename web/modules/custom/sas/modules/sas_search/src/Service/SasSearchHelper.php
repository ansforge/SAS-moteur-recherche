<?php

namespace Drupal\sas_search\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_search\Enum\SasSearchConstant;
use Drupal\sas_search\SolrDataFormatterTrait;
use Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface;
use Drupal\sas_user\Service\SasUserHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SasSuggestedSearchHelper.
 *
 * Helper function for sas search.
 *
 * @package Drupal\sas_search\Service
 */
class SasSearchHelper implements SasSearchHelperInterface {

  use SolrDataFormatterTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $sasUserHelper;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClient;

  /**
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected SasGetTermCodeCitiesInterface $termTerritory;

  /**
   * SasSearchHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   * @param \Drupal\sas_user\Service\SasUserHelperInterface $sas_user_helper
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager $sas_api_client
   * @param \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface $term_territory
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    AccountProxyInterface $account_proxy,
    SasUserHelperInterface $sas_user_helper,
    Connection $database,
    ClientEndpointPluginManager $sas_api_client,
    SasGetTermCodeCitiesInterface $term_territory
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->accountProxy = $account_proxy;
    $this->sasUserHelper = $sas_user_helper;
    $this->database = $database;
    $this->sasApiClient = $sas_api_client;
    $this->termTerritory = $term_territory;
  }

  const INSEE_CODES_TO_CITIES = [
    '13055' => 'Marseille',
    '13201' => 'Marseille',
    '13202' => 'Marseille',
    '13203' => 'Marseille',
    '13204' => 'Marseille',
    '13205' => 'Marseille',
    '13206' => 'Marseille',
    '13207' => 'Marseille',
    '13208' => 'Marseille',
    '13209' => 'Marseille',
    '13210' => 'Marseille',
    '13211' => 'Marseille',
    '13212' => 'Marseille',
    '13213' => 'Marseille',
    '13214' => 'Marseille',
    '13215' => 'Marseille',
    '13216' => 'Marseille',
    '69123' => 'Lyon',
    '69381' => 'Lyon',
    '69382' => 'Lyon',
    '69383' => 'Lyon',
    '69384' => 'Lyon',
    '69385' => 'Lyon',
    '69386' => 'Lyon',
    '69387' => 'Lyon',
    '69388' => 'Lyon',
    '69389' => 'Lyon',
    '75056' => 'Paris',
    '75101' => 'Paris',
    '75102' => 'Paris',
    '75103' => 'Paris',
    '75104' => 'Paris',
    '75105' => 'Paris',
    '75106' => 'Paris',
    '75107' => 'Paris',
    '75108' => 'Paris',
    '75109' => 'Paris',
    '75110' => 'Paris',
    '75111' => 'Paris',
    '75112' => 'Paris',
    '75113' => 'Paris',
    '75114' => 'Paris',
    '75115' => 'Paris',
    '75116' => 'Paris',
    '75117' => 'Paris',
    '75118' => 'Paris',
    '75119' => 'Paris',
    '75120' => 'Paris',
  ];

  /**
   * {@inheritDoc}
   */
  public function getSearchSuggestions(): array {
    $suggestions_cache = $this->cache->get('sas:suggested_search');

    // Get from cache if exists.
    if (!empty($suggestions_cache) && !empty($suggestions_cache->data)) {
      return $suggestions_cache->data;
    }

    // Get search configuration.
    $search_config = $this->configFactory->get('sas_config.search_settings')->get('config');

    $suggestions = [];
    if (!empty($search_config['suggestion_fieldset'])) {
      // Build suggestions list from search config.
      foreach ($search_config['suggestion_fieldset'] as $suggestion) {
        // No suggestion if undefined text.
        if (empty($suggestion['text'])) {
          continue;
        }

        $new_suggestion = [
          'title' => $suggestion['text'],
        ];

        // Get suggestion filters if exists.
        if (!empty($suggestion['filters'])) {
          $new_suggestion['filters'] = $this->getSuggestionFilters($suggestion['filters']);
        }

        if (!empty($suggestion['speciality'])) {
          $new_suggestion['specialities'] = $this->formatSpecialityIdsAsString($suggestion['speciality']);
        }

        $suggestions[] = $new_suggestion;
      }

      $this->cache->set('sas:suggested_search', $suggestions);
    }

    return $suggestions;
  }

  protected function getSuggestionFilters($filters): array {
    $grouped_filters = [];

    try {
      /** @var \Drupal\taxonomy\TermInterface[] $filter_terms */
      $filter_terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadMultiple(array_values($filters));
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return [];
    }

    if (empty($filter_terms)) {
      return [];
    }

    foreach ($filter_terms as $filter_term) {
      $key = SasSearchConstant::SUGGESTIONS_STRUCTURE_SOLR_FIELD;
      if (in_array($filter_term->bundle(),
        SasSearchConstant::SUGGESTION_PRO_VOCABULARY)) {
        $key = SasSearchConstant::SUGGESTIONS_PROFESSIONAL_SOLR_FIELD;
      }
      $grouped_filters[$key][] = $filter_term->getName();
    }

    return $grouped_filters;
  }

  /**
   * {@inheritDoc}
   */
  public function getSuggestedSearch(string $search): array {
    $suggestions = $this->getSearchSuggestions();

    if (empty($suggestions)) {
      return [];
    }

    foreach ($suggestions as $suggestion) {
      if (!empty($suggestion['title']) && $suggestion['title'] === $search && !empty($suggestion['filters'])) {
        return $suggestion;
      }
    }

    return [];
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function getCurrentDay() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->accountProxy->id());
    if (!empty($user)) {
      $timezone = $this->sasUserHelper->getUserRegionTimezone($user);
    }

    if (empty($timezone)) {
      $timezone = date_default_timezone_get();
    }

    $date = new \DateTime('now', new \DateTimeZone($timezone));
    return $date->format('N');
  }

  /**
   * New location LRM.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return string
   *   Returns new location.
   */
  public function newLocation(Request $request): string {
    $parameters = $request->query->all();

    if (empty($parameters)) {
      return '';
    }

    $address_data = $this->getValidAddressData($parameters);
    if (empty($address_data)) {
      return '';
    }

    $elements = [];

    // Ajoutez le numéro et le nom de la rue s'ils sont présents.
    if (!empty($address_data['street_number']) || !empty($address_data['street_name'])) {
      $elements[] = trim(sprintf('%s %s', $address_data['street_number'], $address_data['street_name']));
    }

    // Ajoutez la ville.
    if (!empty($address_data['city'])) {
      $elements[] = $address_data['city'];
    }

    // Ajoutez le département si présent.
    if (!empty($address_data['department'])) {
      $elements[] = $address_data['department'];
    }

    return implode(', ', $elements);
  }

  /**
   * Get valid address data in given data.
   *
   * @param array $data
   *   Data to inspect to check and get address data :
   *     - streetnumber
   *     - streetname
   *     - inseecode
   *     - city.
   *
   * @return array
   *   Address data as an array if given data are valid. Empty array else.
   *   Address data contains :
   *     - street_number
   *     - street_name
   *     - department
   *     - city
   */
  public function getValidAddressData(array $data): array {

    $code_insee = $data['inseecode'] ?? '';
    $city = self::INSEE_CODES_TO_CITIES[$code_insee] ?? $this->termTerritory->sasGetCityFromInseeCode($code_insee) ?? ($data['city'] ?? '');
    $department = $city === 'Paris' ? '' : $this->termTerritory->getDeptNameByInseeCode($code_insee);

    $address_data = [
      'street_number' => empty($data['streetnumber']) ? '' : preg_replace('/[^\d]/', '', $data['streetnumber']),
      'street_name' => ucwords($data['streetname'] ?? ''),
      'city' => ucwords($city),
      'department' => $department,
    ];

    // Missing departement name or city name.
    if (empty($city)) {
      $address_data = [];
    }

    // Street name missing in precise address.
    if (
      !empty($city) &&
      !empty($address_data['street_number']) && empty($address_data['street_name'])
    ) {
      $address_data = [];
    }

    return $address_data;
  }

  public function isValidOrigin(string $origin): bool {
    try {
      $response = $this->sasApiClient->aggregator('lrms');
    }
    catch (PluginException $e) {
      return FALSE;
    }

    if (!empty($response)) {
      foreach ($response as $item) {
        if (!empty($item['affectation_authority']) && $item['affectation_authority'] === $origin) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
