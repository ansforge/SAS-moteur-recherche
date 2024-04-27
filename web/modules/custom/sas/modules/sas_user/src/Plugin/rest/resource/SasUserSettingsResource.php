<?php

namespace Drupal\sas_user\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\sas_entity_snp_user\Entity\SasSnpUserData;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides SAS effectors settings.
 *
 * @RestResource(
 *   id = "sas_user_settings_resource",
 *   label = @Translation("SAS User - Effector settings"),
 *   serialization_class = "\Drupal\sas_entity_snp_user\Entity\SasSnpUserData",
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/user/{idNat}/settings",
 *     "create" = "/sas/api/drupal/user/settings"
 *   }
 * )
 */
class SasUserSettingsResource extends SasUserDataResourceBase {

  /**
   * SAS effector helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

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
    SasEffectorHelperInterface $sasEffectorHelper,
    SasSnpUserDataHelper $sasSnpUserDataHelper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->sasEffectorHelper = $sasEffectorHelper;
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
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns user data.
   */
  public function get(int $idNat): Response {
    $idParts = $this->sasEffectorHelper->getEffectorIdParts($idNat);

    $entity = $this->sasSnpUserDataHelper->getSettingsBy(
      ['user_id' => $idParts['id']],
      TRUE,
      FALSE
    );

    if (!empty($entity)) {
      return new ModifiedResourceResponse($entity);
    }

    return new JsonResponse(
      [
        'code' => Response::HTTP_NOT_FOUND,
        'message' => "Resource not found.",
      ],
      Response::HTTP_NOT_FOUND
    );
  }

  /**
   * Responds to entity POST requests.
   *
   * @param \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $entity
   *   SAS effector settings entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns SAS effector settings data.
   */
  public function post(SasSnpUserData $entity): Response {
    return $this->persistEntity($entity, Response::HTTP_CREATED);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * @param int $idNat
   *   Effector RPPS or ADELI.
   * @param array $data
   *   SAS effector settings data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns SAS effector settings data.
   */
  public function patch(int $idNat, array $data): Response {
    $idParts = $this->sasEffectorHelper->getEffectorIdParts($idNat);

    $entity = $this->sasSnpUserDataHelper->getSettingsBy(
      ['user_id' => $idParts['id']],
      TRUE,
      FALSE
    );

    if (!empty($entity)) {
      foreach ($data as $key => $value) {
        $entity->set($key, $value);
      }

      return $this->persistEntity($entity, Response::HTTP_OK);
    }

    return new JsonResponse(
      [
        'code' => Response::HTTP_NOT_FOUND,
        'message' => "Resource not found.",
      ],
      Response::HTTP_NOT_FOUND
    );
  }

  /**
   * Process POST or PATCH request.
   *
   * @param \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $entity
   *   Created or updated entity.
   * @param int $code
   *   HTTP request code.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns HTTP response.
   */
  private function persistEntity(SasSnpUserData $entity, int $code): Response {
    $violations = $entity->validate();

    if ($violations->count() > 0) {
      $errors = [];

      foreach ($violations as $violation) {
        $errors['errors'][] = [
          'property' => $violation->getPropertyPath(),
          'message' => $violation->getMessage(),
        ];
      }

      return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    try {
      $entity->save();
      return new ModifiedResourceResponse($entity, $code);
    }
    catch (EntityStorageException $e) {
      return new JsonResponse(
        [
          'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
          'message' => $e->getMessage(),
        ],
        Response::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

}
