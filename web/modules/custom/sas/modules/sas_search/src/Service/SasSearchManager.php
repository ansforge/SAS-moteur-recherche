<?php

namespace Drupal\sas_search\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\sante_search_solr\SanteSearchDirectoryEtablishment;
use Drupal\sante_search_solr\SanteSearchDirectoryForgeRequest;
use Drupal\sante_search_solr\SanteSearchDirectoryLocalisation;
use Drupal\sante_search_solr\SanteSearchSolrDirectory;
use Drupal\sante_search_solr\SanteSearchSolrManager;
use Drupal\sas_geolocation\Service\SasGeolocationHelperInterface;
use Drupal\sas_search\Enum\SasSolrQueryConstant;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SasSearchManager.
 *
 * Sas Search Manager to make query and treat results.
 *
 * This service is base on sante.fr directory search to inherit for
 * post query data process.
 *
 * @package Drupal\sas_search\Service
 */
class SasSearchManager extends SanteSearchSolrDirectory implements SasSearchManagerInterface {

  /**
   * Sas Solr query manager service.
   *
   * @var \Drupal\sas_search\Service\SasSolrQueryManager
   */
  protected SasSolrQueryManager $sasSolrQueryManager;

  /**
   * Sas solr practitioner query manager.
   *
   * @var \Drupal\sas_search\Service\SasSolrPractitionerQueryManager
   */
  protected SasSolrPractitionerQueryManager $solrPractitionerQuery;

  /**
   * Processed parameters.
   *
   * @var array
   */
  protected $processedParameters;

  /**
   * @var \Drupal\sas_geolocation\Service\SasGeolocationHelperInterface
   */
  protected SasGeolocationHelperInterface $sasGeoloc;

  /**
   * SasSearchManager constructor.
   *
   * @param \Drupal\sante_search_solr\SanteSearchDirectoryLocalisation $localisationHelpers
   * @param \Drupal\sante_search_solr\SanteSearchDirectoryForgeRequest $forgeRequest
   * @param \Drupal\sante_search_solr\SanteSearchSolrManager $solrManager
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\sante_search_solr\SanteSearchDirectoryEtablishment $etablishmentHelpers
   * @param \Drupal\sas_search\Service\SasSolrQueryManagerInterface $sas_solr_query_manager
   *   Solr query manager service for main search query.
   * @param \Drupal\sas_search\Service\SasSolrPractitionerQueryManager $solr_practitioner_query
   *   Practitioner solr query manager service for preferred doctor query.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(
    SanteSearchDirectoryLocalisation $localisationHelpers,
    SanteSearchDirectoryForgeRequest $forgeRequest,
    SanteSearchSolrManager $solrManager,
    StateInterface $state,
    RequestStack $request_stack,
    EntityTypeManagerInterface $entityTypeManager,
    CacheBackendInterface $cache,
    SanteSearchDirectoryEtablishment $etablishmentHelpers,
    SasSolrQueryManagerInterface $sas_solr_query_manager,
    SasSolrPractitionerQueryManager $solr_practitioner_query,
    SasGeolocationHelperInterface $sas_geolocation_helper
  ) {
    parent::__construct($localisationHelpers, $forgeRequest, $solrManager, $state,
      $request_stack, $entityTypeManager, $cache, $etablishmentHelpers);
    $this->sasSolrQueryManager = $sas_solr_query_manager;
    $this->solrPractitionerQuery = $solr_practitioner_query;
    $this->sasGeoloc = $sas_geolocation_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function makeSearch() {
    $result = [];
    $start_time = microtime(TRUE);

    // Check query parameters.
    $error = $this->sasSolrQueryManager->checkQueryParameters();
    if (!empty($error)) {
      return $error;
    }

    // Build and execute solr query.
    $this->sasSolrQueryManager->buildQuery();
    $request_time = microtime(TRUE);
    $solr_result = json_decode($this->sasSolrQueryManager->executeQuery());

    // Treat establishment if etb=treat passed in query parameters.
    if ($this->requestStack->get('etb') == 'treat') {
      $this->highlightingTreat($solr_result);
      $params = $this->getProcessedParameters();
      $this->etablissementTreat($solr_result, $params);
    }

    // Insert solr response data in endpoint result.
    $result['data'] = $solr_result;

    // Insert search location data in endpoint result.
    $result['infos']['location'] = [
      'center_lat' => $this->requestStack->query->get('center_lat') ?? '',
      'center_lon' => $this->requestStack->query->get('center_lon') ?? '',
      'radius' => $this->requestStack->query->get('radius') ?? '',
    ];

    // Insert pagination data in endpoint result.
    $pagination = $this->sasSolrQueryManager->getPagination();
    $result['infos']['start'] = $pagination['offset'] + 1;
    $result['infos']['page'] = $pagination['page'];
    $result['infos']['length'] = isset($solr_result->grouped->ss_field_custom_group->groups) ? count($solr_result->grouped->ss_field_custom_group->groups) : 0;
    $result['infos']['end'] = $pagination['offset'] + $result['infos']['length'];

    // Query additional data.
    $result['infos']['ip_client'] = $this->requestStack->getClientIp();
    $result['infos']['timeStart'] = $start_time;
    $result['infos']['timeReq'] = $request_time ?? NULL;
    $end_time = microtime(TRUE);
    $result['infos']['timeEnd'] = $end_time;
    $result['infos']['time'] = sprintf('%s ms', ceil(($end_time - $start_time) * 1000));

    return $result;
  }

  /**
   * Make preferred doctor search.
   *
   * @return array
   *   List of places for preferred doctor OR error message.
   */
  public function makePreferredDoctorSearch(): array {
    $query_result = $this->makePreferredDoctorQuery();
    if (!empty($query_result['error_code_sas'])) {
      return $query_result;
    }
    foreach ($query_result as $key => $doc) {
      if (empty($doc->ss_field_identifiant_rpps)) {
        unset($query_result[$key]);
      }

      if (empty($rpps)) {
        $rpps = $doc->ss_field_identifiant_rpps;
      }

      if ($rpps !== $doc->ss_field_identifiant_rpps) {
        return SasSolrQueryConstant::PRACTITIONER_NO_RESULT_ERROR;
      }
    }

    return $query_result;
  }

  /**
   * Query preferred doctor search.
   *
   * @return array
   *   List of places for preferred doctor OR error message.
   */
  public function makePreferredDoctorQuery(): array {
    if (!empty($this->requestStack->get('pref_doctor'))) {
      // Make practitioner solr query to get preferred doctor.
      $this->solrPractitionerQuery->buildQuery();
      $practitioner_results = json_decode($this->solrPractitionerQuery->executeQuery());

      if (empty($practitioner_results->response->docs)) {
        return SasSolrQueryConstant::PRACTITIONER_NO_RESULT_ERROR;
      }
      return $practitioner_results->response->docs;
    }
    return SasSolrQueryConstant::PRACTITIONER_NO_PREF_DOC_ERROR;
  }

  /**
   * Make an enlarge search.
   */
  protected function makeEnlargedSearch() {
    /** @var \Drupal\sas_geolocation\Model\SasLocation $search_location */
    $search_location = $this->sasSolrQueryManager->getLocation();
    $area_coordinates = $search_location->getAreaCoordinates();

    // Calculate interval to add.
    $latToAdd = abs($area_coordinates['ne_lat'] - $area_coordinates['so_lat']) * SasSolrQueryConstant::SOLR_QUERY_NO_RESULT_ENLARGEMENT_COEF;
    $lonToAdd = abs($area_coordinates['ne_lon'] - $area_coordinates['so_lon']) * SasSolrQueryConstant::SOLR_QUERY_NO_RESULT_ENLARGEMENT_COEF;

    $so_lat = $area_coordinates['so_lat'] - $latToAdd;
    $ne_lat = $area_coordinates['ne_lat'] + $latToAdd;
    $so_lon = $area_coordinates['so_lon'] - $lonToAdd;
    $ne_lon = $area_coordinates['ne_lon'] + $lonToAdd;

    // Store enlargement data, build new query and execute it.
    $this->sasSolrQueryManager->setSearchEnlargementArea($ne_lat, $ne_lon, $so_lat, $so_lon);
    $this->requestStack->query->set('relance', 1);
    $this->sasSolrQueryManager->buildQuery();
    return json_decode($this->sasSolrQueryManager->executeQuery());
  }

  /**
   * Get processed parameters to be used by post request treatment.
   *
   * Needed to use some sante.fr treatment.
   *
   * @param bool $forceRebuild
   *   Set to TRUE to force parameters to be pocessed.
   *
   * @return array
   *   Processed parameter ready to use.
   *
   * @throws \Exception
   */
  protected function getProcessedParameters(bool $forceRebuild = FALSE): array {

    if (!empty($this->processedParameters) && !$forceRebuild) {
      return $this->processedParameters;
    }

    $processed_parameters = [];

    $processed_parameters['what'] = $this->requestStack->query->get('what');
    $processed_parameters['what_transliteration'] = $this->sasSolrQueryManager->getQueryParameterTransliterated('what');

    $processed_parameters['type_what'] = 'normal';
    if (str_contains($processed_parameters['what_transliteration'], 'urgence')) {
      $processed_parameters['type_what'] = 'urgence';
    }

    if (str_contains($processed_parameters['what_transliteration'], 'maternite')) {
      $processed_parameters['type_what'] = 'maternite';
    }

    $processed_parameters['where'] = $this->requestStack->query->get('where');
    $processed_parameters['rand_id'] = !empty($this->requestStack->query->get('rand_id'))
      ? $this->requestStack->query->get('rand_id')
      : strval(random_int(0, 9999999999));
    $processed_parameters['etb'] = !empty($this->requestStack->query->get('etb')) ? $this->requestStack->query->get('etb') : 'treat';

    // Force to false in sas context to avoid warning in establishment treat.
    $processed_parameters['thematic_card'] = FALSE;

    $this->processedParameters = $processed_parameters;
    return $processed_parameters;
  }

}
