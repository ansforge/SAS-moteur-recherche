<?php

namespace Drupal\sas_orientation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for SAS-API orientations.
 */
class SasOrientationController extends ControllerBase {

  /**
   * The SAS API client manager.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClientService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->sasApiClientService = $container->get('sas_api_client.service');

    return $instance;
  }

  /**
   * Create an orientation in SAS-API for specific slot.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON response.
   */
  public function createOrientation(Request $request) {
    $response = NULL;
    $orientation = json_decode($request->getContent(), TRUE);

    if (!empty($orientation)) {
      $response = $this->sasApiClientService->sas_api('orientation', [
        'body' => $orientation,
      ]);
    }

    return new JsonResponse($response);
  }

}
