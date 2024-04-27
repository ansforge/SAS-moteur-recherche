<?php

declare(strict_types = 1);

namespace Drupal\sas_user\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_user_dashboard\Services\SasDashboardSosDoctors;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a resource for retrieving SOS Doctors and PFG lists.
 *
 * @RestResource(
 *   id = "sas_user_sos_doctors",
 *   label = @Translation("SAS User - Sos Doctors"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/sos-doctors/list"
 *   }
 * )
 */
class SasUserSosDoctorList extends SasUserDataResourceBase {

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\SasDashboardSosDoctors
   */
  protected SasDashboardSosDoctors $sasDashboardSosDoctors;

  /**
   * Constructs a new SasUserSosDoctorList object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   * */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    SasDashboardSosDoctors $sasDashboardSosDoctors
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->sasDashboardSosDoctors = $sasDashboardSosDoctors;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
          $plugin_id,
          $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('sas_user_dashboard.sos_doctors')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   *
   *   Returns a list of SOS Doctors and PFG for a given user ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns user data.
   */
  public function get(Request $request): JsonResponse {
    $user_id = $request->get('userId');
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $response->addCacheableDependency($cacheableMetadata);

    if (!$user_id) {
      return new JsonResponse(
        [
          'code' => Response::HTTP_NOT_FOUND,
          'message' => "User id NULL or Resource not found.",
        ],
        Response::HTTP_NOT_FOUND
      );
    }

    // Get Sos Médecin Association and PFG.
    $association_list = [];
    $user = User::load($user_id);
    if (!$user) {
      return new JsonResponse(
        [
          'code' => Response::HTTP_NOT_FOUND,
          'message' => "User not found.",
        ],
        Response::HTTP_NOT_FOUND
      );
    }
    if ($user->hasField(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)
      && !$user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->isEmpty()) {
      $siret_list = array_column($user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->getValue(), 'value');
      $association_list = $this->sasDashboardSosDoctors->getSosMedecinAssociationsList($siret_list, $user);
    }
    if (!empty($association_list)) {
      $cacheableMetadata->addCacheContexts(
        cache_contexts: [
          'url.query_args:userId',
        ]);
      $cacheableMetadata->setCacheMaxAge(max_age: 1300);
    }
    $data = [
      'userId' => $user_id,
      'list' => [$association_list],
    ];

    return new JsonResponse($data);
  }

}
