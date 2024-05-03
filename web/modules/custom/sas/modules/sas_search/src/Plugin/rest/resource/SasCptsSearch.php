<?php

declare(strict_types = 1);

namespace Drupal\sas_search\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\sas_search\Service\SasCptsSearchManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a SAS search cpts Resource.
 *
 * @RestResource(
 *   id = "sas_search_cpts",
 *   label = @Translation("SAS search - CPTS"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/search/cpts"
 *   }
 * )
 */
class SasCptsSearch extends ResourceBase {

  /**
   * Sas Search manager.
   *
   * @var \Drupal\sas_search\Service\SasCptsSearchManager
   */
  protected SasCptsSearchManager $sasCptsSearchManager;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    SasCptsSearchManager $sas_cpts_search_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->sasCptsSearchManager = $sas_cpts_search_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('sas_search.cpts.manager')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function get(): JsonResponse {
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(
      cache_contexts: ['url.query_args:code_insee']
    );
    $cacheableMetadata->setCacheMaxAge(max_age: 1300);
    $response->setData($this->sasCptsSearchManager->makeCptsQuery());
    $response->addCacheableDependency($cacheableMetadata);
    return $response;
  }

}
