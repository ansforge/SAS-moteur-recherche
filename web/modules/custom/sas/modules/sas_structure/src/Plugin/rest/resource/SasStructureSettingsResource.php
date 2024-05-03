<?php

namespace Drupal\sas_structure\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\ResourceResponse;
use Drupal\sas_core\Plugin\SasResourceBase;
use Drupal\sas_structure\Entity\SasStructureSettings;
use Drupal\sas_structure\Service\StructureSettingsHelperInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides structure settings.
 *
 * @RestResource(
 *   id = "sas_structure_settings",
 *   label = @Translation("SAS Structure - Settings"),
 *   entity_type = "sas_stucture_settings",
 *   serialization_class = "Drupal\sas_structure\Entity\SasStructureSettings",
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/structure/{structure_id}/settings",
 *     "create" = "/sas/api/drupal/structure/settings"
 *   }
 * )
 */
class SasStructureSettingsResource extends SasResourceBase {

  private const MANAGED_PROPERTIES = [
    'structure_id',
    'id_type',
    'sas_participation',
    'hours_available',
    'practitioner_count',
  ];

  /**
   * @var \Drupal\sas_structure\Service\StructureSettingsHelperInterface
   */
  protected StructureSettingsHelperInterface $structureSettingsHelper;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxy $current_user,
    StructureSettingsHelperInterface $structureSettingsHelper,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );
    $this->currentUser = $current_user;
    $this->structureSettingsHelper = $structureSettingsHelper;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('current_user'),
      $container->get('sas_structure.settings_helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param string $structure_id
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns structure data.
   */
  public function get(string $structure_id): Response {
    $structure_settings = $this->structureSettingsHelper->getSettingsBy([
      'structure_id' => $structure_id,
    ], FALSE);

    if (!empty($structure_settings)) {
      return new ResourceResponse($structure_settings);
    }

    return new JsonResponse(
      [
        'message' => "Resource not found.",
      ],
      Response::HTTP_NOT_FOUND
    );
  }

  /**
   * Responds to entity PATCH requests.
   *
   * @param string $structure_id
   *   Structure ID.
   * @param \Drupal\sas_structure\Entity\SasStructureSettings $new_structure_settings
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns SAS Structure settings data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function patch(string $structure_id, SasStructureSettings $new_structure_settings): Response {
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings */
    $structure_settings = $this->entityTypeManager->getStorage('sas_structure_settings')
      ->loadByStructureId($structure_id);

    if (!empty($structure_settings)) {
      foreach (self::MANAGED_PROPERTIES as $name) {
        $structure_settings->set($name, $new_structure_settings->$name->value);
      }
      return $this->persistEntityStructureSettings($structure_settings, Response::HTTP_OK);
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
   * @param \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns SAS effector settings data.
   */
  public function post(SasStructureSettings $structure_settings): Response {
    return $this->persistEntityStructureSettings($structure_settings, Response::HTTP_CREATED);
  }

  /**
   * @param \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings
   * @param int $code
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  private function persistEntityStructureSettings(SasStructureSettings $structure_settings, int $code): Response {

    // Set last updated date before entity validation.
    $structure_settings->get('updated')->setValue(time());
    $structure_settings->get('uid')->setValue($this->currentUser->id());

    $errors = $this->validateEntity($structure_settings);

    if (!empty($errors)) {
      return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    try {
      $structure_settings->save();
      return new ModifiedResourceResponse($structure_settings, $code);
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
