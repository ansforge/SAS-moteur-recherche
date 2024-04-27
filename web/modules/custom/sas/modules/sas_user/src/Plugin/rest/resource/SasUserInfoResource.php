<?php

namespace Drupal\sas_user\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_keycloak\Service\SasKeycloakPscUser;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_user\Service\SasStructureManagerHelper;
use Drupal\sas_user\Service\SasUserHelperInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a SAS User Info Resource.
 *
 * @RestResource(
 *   id = "sas_user_info_resource",
 *   label = @Translation("SAS User - Basic information"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/user/info"
 *   }
 * )
 */
class SasUserInfoResource extends SasUserDataResourceBase {

  /**
   * @var \Drupal\sas_user\Service\SasStructureManagerHelper
   */
  protected SasStructureManagerHelper $sasStructureManagerHelper;

  /**
   * DashboardUser service.
   *
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $userHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    SasStructureManagerHelper $sasStructureManagerHelper,
    SasUserHelperInterface $userHelper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->sasStructureManagerHelper = $sasStructureManagerHelper;
    $this->userHelper = $userHelper;
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
      $container->get('sas_user.structure_manager_helper'),
      $container->get('sas_user.helper')
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
  public function get(Request $request): JsonResponse {
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(['user']);
    $response->addCacheableDependency($cacheableMetadata);

    $user_id = $request->get('userId');
    $user = $this->userHelper->getUserData($user_id);

    if (empty($user)) {
      return $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    // Gestion des cas ou le user n'a pas de compte drupal
    // Si $user est un ProfessionnelDeSanteSas ou PSC is_sos_manager est false.
    $is_sos_manager = $user instanceof User &&
      $this->sasStructureManagerHelper->isStructureManager(
        $user,
        StructureConstant::SOS_MEDECIN_USER_FIELD_NAME
      );

    $response->setData(match (get_class($user)) {
      User::class => [
        'uid' => $user->id(),
        'name' => sprintf('%s %s', $user->field_sas_nom->value, $user->field_sas_prenom->value),
        'id_nat' => $user->field_sas_rpps_adeli->value,
        'email' => $user->getEmail(),
        'isSosManager' => $is_sos_manager,
      ],
      SasKeycloakPscUser::class => [
        'uid' => NULL,
        'name' => sprintf('%s %s', $user->get('lastname'), $user->get('firstname')),
        'id_nat' => $user->getPscIdNat(),
        'email' => $user->get('email'),
        'isSosManager' => $is_sos_manager,
      ],
      ProfessionnelDeSanteSas::class => [
        'uid' => NULL,
        'name' => $user->getTitle(),
        'id_nat' => $user->getNationalIdAsString(),
        'email' => NULL,
        'isSosManager' => $is_sos_manager,
      ],
      default => [],
    });

    return $response;
  }

}
