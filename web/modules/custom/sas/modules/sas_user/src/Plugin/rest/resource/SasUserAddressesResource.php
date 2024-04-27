<?php

namespace Drupal\sas_user\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides SAS effectors addresses list.
 *
 * @RestResource(
 *   id = "sas_user_adresses_resource",
 *   label = @Translation("SAS User - Effector addresses list"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/user/{idNat}/addresses"
 *   }
 * )
 */
class SasUserAddressesResource extends SasUserDataResourceBase {

  /**
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $effectorHelper;

  /**
   * Sas snp user data helper.
   *
   * @var \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper
   */
  protected SasSnpUserDataHelper $sasSnpUserDataHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    SasEffectorHelperInterface $effector_helper,
    SasSnpUserDataHelper $sasSnpUserDataHelper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->effectorHelper = $effector_helper;
    $this->sasSnpUserDataHelper = $sasSnpUserDataHelper;
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
      $container->get('sas_user.effector_helper'),
      $container->get('sas_snp_user_data.helper')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param int $idNat
   *   Effector RPPS or ADELI.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns user data.
   */
  public function get(int $idNat): JsonResponse {
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(['user']);
    $currentUser = $this->effectorHelper->getCurrentUser();

    // Flag to get only place link to a cpts if account is a structure manager.
    $cpts_only = in_array(
      SnpConstant::SAS_GESTIONNAIRE_STRUCTURE,
      $currentUser->getRoles()
    );

    $addresses = $this->effectorHelper->getAddresses($idNat, $cpts_only);
    $filteredAddresses = array_values(array_filter($addresses));

    if (!empty($filteredAddresses)) {
      $response->setData($filteredAddresses);
      $cacheableMetadata->addCacheTags(
        array_map(
          static function ($address) {
            return sprintf('node:%s', $address['sheet_nid']);
          },
          $filteredAddresses
        )
      );
    }

    $response->addCacheableDependency($cacheableMetadata);

    return $response;
  }

}
