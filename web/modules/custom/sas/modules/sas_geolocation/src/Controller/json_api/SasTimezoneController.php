<?php

namespace Drupal\sas_geolocation\Controller\json_api;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\sas_geolocation\Service\SasTimezoneHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for timezone places.
 */
class SasTimezoneController extends ControllerBase {

  /**
   * @var \Drupal\sas_geolocation\Service\SasTimezoneHelper
   */
  protected SasTimezoneHelper $timezoneHelper;

  /**
   * The SasTimezoneController constructor.
   *
   * @param \Drupal\sas_geolocation\Service\SasTimezoneHelper $timezoneHelper
   *   Timezone Helper.
   */
  public function __construct(SasTimezoneHelper $timezoneHelper) {
    $this->timezoneHelper = $timezoneHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_geolocation.timezone')
    );
  }

  /**
   * Get place timezone.
   */
  public function getPlaceTimezone(Node $node): JsonResponse {
    $response = new JsonResponse();
    $timezone = $this->timezoneHelper->getPlaceTimezone($node);
    $response->setData($timezone);
    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $response;
  }

}
