<?php

namespace Drupal\sas_snp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_snp\Service\SnpContentHelper;
use Drupal\sas_snp\Service\SnpUnavailabilityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Class SasUnavailabilityController.
 */
class SasUnavailabilityController extends ControllerBase {

  /**
   * The sas_api_client.service service.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClientService;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_snp\Service\SnpUnavailabilityHelper
   */
  protected SnpUnavailabilityHelper $sasSnpUnavailabilityHelper;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelper
   */
  protected SnpContentHelper $sasSnpContentHelper;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->sasApiClientService = $container->get('sas_api_client.service');
    $instance->sasSnpUnavailabilityHelper = $container->get('sas_snp.unavailability_helper');
    $instance->sasSnpContentHelper = $container->get('sas_snp.content_helper');
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * Return SAS-API slots for search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $schedule_id
   *   The schedule id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON response.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function getSlotsByScheduleWithoutUnavailability(Request $request, int $schedule_id) {
    $slots = [];
    $response = $this->sasApiClientService->sas_api('schedule', [
      'query' => [
        'start_date' => $request->query->get('start_date'),
        'end_date' => $request->query->get('end_date'),
        'orientationStrategy' => $request->query->get('orientationStrategy'),
        'show_expired' => $request->query->get('show_expired'),
      ],
      'tokens' => [
        'id' => $schedule_id,
      ],
    ]);

    $slotRef = $this->sasSnpContentHelper->getSlotRefByScheduleId($schedule_id);

    if (!empty($response) && $slotRef) {
      $formatedResponse = [];
      foreach ($response as $value) {
        $formatedResponse['slots'][] = $value;
      }
      $formatedResponse['nid'] = $slotRef[0]->slot_ref_target_id;
      unset($response);
      $response = $this->sasSnpUnavailabilityHelper->getUnavalaibilities([$slotRef[0]->slot_ref_target_id => $formatedResponse]);
      $slots = array_values(reset($response)['slots']);
    }

    return new JsonResponse($slots);

  }

}
