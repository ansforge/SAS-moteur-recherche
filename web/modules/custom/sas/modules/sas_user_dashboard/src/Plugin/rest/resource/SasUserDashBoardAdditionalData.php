<?php

declare(strict_types = 1);

namespace Drupal\sas_user_dashboard\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sas_search_index\Service\SasSearchIndexHelperInterface;
use Drupal\sas_user\Plugin\rest\resource\SasUserDataResourceBase;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a REST resource for the SAS user dashboar.
 *
 * @RestResource(
 *   id = "sas_user_additional_data",
 *   label = @Translation("SAS User Dashboard - Additional Data"),
 *   uri_paths = {
 *     "canonical" = "/sas/api/drupal/additional-data",
 *     "create" = "/sas/api/drupal/additional-data"
 *   }
 * )
 */
class SasUserDashBoardAdditionalData extends SasUserDataResourceBase {

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * The entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SAS search index helper service.
   *
   * @var \Drupal\sas_search_index\Service\SasSearchIndexHelperInterface
   */
  protected SasSearchIndexHelperInterface $sasSearchIndexHelper;

  /**
   * Constructs a new SasUserDashBoardAdditionalData object.
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
   *   A logger instance.
   * @param \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\sas_search_index\Service\SasSearchIndexHelperInterface $sasSearchIndexHelper
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    DashboardUserInterface $dashboard,
    EntityTypeManagerInterface $entityTypeManager,
    SasSearchIndexHelperInterface $sasSearchIndexHelper
    ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->dashboard = $dashboard;
    $this->entityTypeManager = $entityTypeManager;
    $this->sasSearchIndexHelper = $sasSearchIndexHelper;
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
      $container->get('sas_user_dashboard.dashboard'),
      $container->get('entity_type.manager'),
      $container->get('sas_search_index.helper')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function get(Request $request) {
    $nid = $request->get('nid');
    $response = new JsonResponse();

    if (empty($nid)) {
      return new JsonResponse(['message' => "No NID provided."], Response::HTTP_NOT_FOUND);
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($node) {
      $additional_info = $this->dashboard->getTimeSlotAdditionalInfo($node);

      if ($additional_info !== NULL) {
        return $response->setData($additional_info);
      }
    }
    return new JsonResponse(['message' => "Resource not found."], Response::HTTP_NOT_FOUND);
  }

  /**
   * Responds to POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function post(Request $request): JsonResponse|Response {
    $data = json_decode($request->getContent(), TRUE);
    $nid = $data['nid'];
    $additional_data = $data['additional_data'];

    if (empty($nid)) {
      return new JsonResponse(
        [
          'message' => 'The Nid is not valid',
        ],
        Response::HTTP_BAD_REQUEST
      );
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    $this->dashboard->handleTimeSlot($node, $additional_data);

    // Forcer l'indexation.
    $this->sasSearchIndexHelper->indexSpecificItem((int) $node->id());
    return new JsonResponse(['message' => 'Content created or updated'], Response::HTTP_CREATED);

  }

}
