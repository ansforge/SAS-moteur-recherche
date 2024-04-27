<?php

namespace Drupal\sas_structure\Plugin\rest\resource;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\sas_core\Plugin\SasResourceBase;
use Drupal\sas_structure\Service\StructureAutocompleteServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides structure list.
 *
 * @RestResource(
 *   id = "sas_structure_list",
 *   label = @Translation("SAS Structure - Structure list"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/structure/list/{type}"
 *   }
 * )
 */
class SasStructureList extends SasResourceBase {

  /**
   * @var \Drupal\sas_structure\Service\StructureAutocompleteServiceInterface
   */
  protected StructureAutocompleteServiceInterface $structureAutocomplete;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StructureAutocompleteServiceInterface $structure_autocomplete
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $serializer_formats, $logger);
    $this->structureAutocomplete = $structure_autocomplete;
  }

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
      $container->get('sas_structure.autocomplete')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns user data.
   */
  public function get(Request $request, string $type): JsonResponse {
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheTags(cache_tags: ['node_list']);
    $cacheableMetadata->addCacheContexts(cache_contexts: [
      'url.query_args:search',
    ]);
    $response->addCacheableDependency($cacheableMetadata);

    $search = Xss::filter($request->query->get(key: 'search', default: ''));

    $matches = $this->structureAutocomplete->structureAutocomplete($type, $search);

    $response->setData($matches);

    return $response;
  }

}
