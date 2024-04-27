<?php

namespace Drupal\sas_search\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sante_search_solr\Controller\SanteSearchSolrPageDirectory;
use Drupal\sas_search\Service\SasSearchManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for SAS search routes.
 */
class JsonApiSearchRequestController extends ControllerBase {

  /**
   * Sas Search manager.
   *
   * @var \Drupal\sas_search\Service\SasSearchManagerInterface
   */
  protected SasSearchManagerInterface $sasSearchManager;

  /**
   * SearchRequestController constructor.
   *
   * @param \Drupal\sas_search\Service\SasSearchManagerInterface $sas_search_manager
   */
  public function __construct(SasSearchManagerInterface $sas_search_manager) {
    $this->sasSearchManager = $sas_search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_search.manager'),
    );
  }

  /**
   * Make search and return search results as json response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Search results.
   */
  public function getResults() {
    $result = $this->sasSearchManager->makeSearch();
    return new JsonResponse($result, $result['error_code'] ?? Response::HTTP_OK);
  }

  /**
   * Make pref doctor search and return search results as json response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Search results.
   */
  public function getPrefDoctorResults() {
    $result = $this->sasSearchManager->makePreferredDoctorSearch();
    return new JsonResponse($result, $result['error_code'] ?? Response::HTTP_OK);
  }

  /**
   * Get SAS search suggestions as json response.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Search suggestions.
   */
  public function getSuggestions() {
    $response = new CacheableJsonResponse();
    $data = [];
    $config = $this->config('sas_config.search_settings');
    if ($config instanceof ImmutableConfig) {
      $response->addCacheableDependency($config);
      $suggestions = $config->get('config.suggestion_fieldset');
      if (is_array($suggestions)) {
        $data = array_column($suggestions, 'text');
      }
    }

    $response->setData($data);
    return $response;
  }

  /**
   * Get SAS search suggestions as json response.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Search suggestions.
   */
  public function isStructureMapping(Request $request) {
    $search = $request->query->get('search_text');
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->setCacheContexts(['url.query_args:search_text']);
    $response = new CacheableJsonResponse(NULL, 400);
    $response->addCacheableDependency($cacheableMetadata);
    $data = [
      'http_code' => 400,
      'error' => 'Missing parameter "search_text".',
    ];

    if ($search) {
      $data = [
        'http_code' => 200,
        'isStructureSearch' => FALSE,
      ];
      $config = $this->config('sas_config.search_settings');
      if ($config) {
        $response->addCacheableDependency($config);
        $suggestions = explode("\r\n", $config->get('config.mapping'));
        if (in_array($search, $suggestions)) {
          $data['isStructureSearch'] = TRUE;
        }
      }

      $response->setStatusCode(200);
    }

    $response->setData($data);
    return $response;
  }

  /**
   * Get SAS search dictionnaries as json response.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Search dictionnaries.
   */
  public function searchDictionnary() {
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheTags([SanteSearchSolrPageDirectory::DICTIONNARY_CID]);
    $response = new CacheableJsonResponse();
    $response->addCacheableDependency($cacheableMetadata);
    $data = SanteSearchSolrPageDirectory::dictionnaryLoad();
    $response->setData($data);
    return $response;
  }

}
