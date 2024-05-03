<?php

namespace Drupal\sas_structure\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\sas_core\Plugin\SasResourceBase;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Service\StructureHelperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides structure basic information.
 *
 * @RestResource(
 *   id = "sas_structure_info",
 *   label = @Translation("SAS Structure - Basic information"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/structure/{id_structure}/{id}/info"
 *   }
 * )
 */
class SasStructureInfo extends SasResourceBase {

  /**
   * @var \Drupal\sas_structure\Service\StructureHelperInterface
   */
  protected StructureHelperInterface $structureHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StructureHelperInterface $structure_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $serializer_formats, $logger);
    $this->structureHelper = $structure_helper;
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
      $container->get('sas_structure.helper')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param string $id_structure
   *   Type of id provided. (cpts/msp/sos)
   * @param string $id
   *   Structure id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns user data.
   */
  public function get(string $id_structure, string $id): JsonResponse {
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $response->addCacheableDependency($cacheableMetadata);

    if (in_array($id_structure, StructureConstant::getStructureTypes())) {
      $data = $this->structureHelper->getStructureBasicInfo($id_structure, $id);
      if (empty($data)) {
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
      }
      else {
        if (!empty($data['nid'])) {
          $cacheableMetadata->addCacheTags(
            cache_tags: [
              sprintf('node:%s', $data['nid']),
            ]
          );
        }
        $response->setData($data);
      }
    }
    else {
      $response->setData('Invalid ID Type.');
      $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    }

    return $response;
  }

}
