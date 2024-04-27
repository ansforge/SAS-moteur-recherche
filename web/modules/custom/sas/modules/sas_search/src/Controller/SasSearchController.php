<?php

namespace Drupal\sas_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\sas_api_client\Enum\SasAnalitycsLogConstant;
use Drupal\sas_api_client\Service\SasAnalyticsLogServiceInterface;
use Drupal\sas_geolocation\Service\SasGeolocationHelperInterface;
use Drupal\sas_search\Service\SasSearchHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for SAS Search routes.
 */
class SasSearchController extends ControllerBase {

  /**
   * Sas Search helper.
   *
   * @var \Drupal\sas_search\Service\SasSearchHelperInterface
   */
  protected SasSearchHelperInterface $sasSearchHelper;

  /**
   * @var \Drupal\sas_api_client\Service\SasAnalyticsLogServiceInterface
   */
  protected SasAnalyticsLogServiceInterface $analyticsLogger;

  /**
   * @var \Drupal\sas_geolocation\Service\SasGeolocationHelperInterface
   */
  protected SasGeolocationHelperInterface $geolocationHelper;

  /**
   * @var \Drupal\Core\Site\Settings
   */
  protected Settings $settings;

  /**
   * SasSearchController constructor.
   *
   * @param \Drupal\sas_search\Service\SasSearchHelperInterface $sas_search_helper
   * @param \Drupal\sas_api_client\Service\SasAnalyticsLogServiceInterface $analytics_logger
   * @param \Drupal\sas_geolocation\Service\SasGeolocationHelperInterface $geolocation_helper
   */
  public function __construct(
    Settings $settings,
    SasSearchHelperInterface $sas_search_helper,
    SasAnalyticsLogServiceInterface $analytics_logger,
    SasGeolocationHelperInterface $geolocation_helper
  ) {
    $this->settings = $settings;
    $this->sasSearchHelper = $sas_search_helper;
    $this->analyticsLogger = $analytics_logger;
    $this->geolocationHelper = $geolocation_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('settings'),
      $container->get('sas_search.helper'),
      $container->get('sas_api_client.analytics.log'),
      $container->get('sas_geolocation.helper')
    );
  }

  /**
   * Builds the response.
   */
  public function build(Request $request) {
    $parameters = $request->query->all();

    $address = $parameters['loc'] ?? '';

    // If origin parameter provided, it's LRM search.
    if (array_key_exists('origin', $parameters)) {
      if (!$this->sasSearchHelper->isValidOrigin($request->query->get('origin'))) {
        $this->analyticsLogger->pushLog(
          log_name: 'LrmRedirectionStatus',
          data: [
            'redirectTo' => SasAnalitycsLogConstant::LOG_SEARCH_REDIRECT_ERROR,
            'origin' => $request->query->get('origin'),
            'url' => $request->getUri(),
          ]
        );
        return $this->getErrorRender($this->t(
          "Le flux entre le logiciel de régulation médicale à l'origine de la recherche
          et la plateforme numérique SAS est désactivé pour des raisons techniques.
          Veuillez contacter le support pour plus de renseignements."
        ));
      }

      // Check if address components are provided and redirect to homepage if not.
      $address = $this->sasSearchHelper->newLocation($request);
      $this->analyticsLogger->pushLog(
        log_name: 'LrmUrlAddressResult',
        data: [
          'success' => (int) !empty($address),
          'failed' => (int) empty($address),
        ]
      );
      if (empty($address)) {
        $this->analyticsLogger->pushLog(
          log_name: 'LrmRedirectionStatus',
          data: [
            'redirectTo' => SasAnalitycsLogConstant::LOG_SEARCH_REDIRECT_HOMEPAGE,
            'origin' => $request->query->get('origin'),
            'url' => $request->getUri(),
          ]
        );
        return new RedirectResponse(Url::fromRoute('<front>')->toString());
      }
    }

    $location = $this->geolocationHelper->getPlaceLocation($address);

    return [
      'content' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'sas-search-wrapper',
        ],
        '#cache' => [
          'max-age' => 3600,
          'contexts' => [
            'url.query_args:loc',
            'url.query_args:origin',
            'url.query_args:practitioner',
            'url.query_args:speciality',
            'url.query_args:streetnumber',
            'url.query_args:streetname',
            'url.query_args:inseecode',
            'url.query_args:city',
          ],
        ],
        '#attached' => [
          'library' => [
            'sas_vuejs/search-page',
          ],
          'drupalSettings' => [
            'sas_vuejs' => [
              'parameters' => [
                'location' => $location?->getObject(),
                'location_status' => !empty($location),
                'location_input' => $address ?? '',
                'maptiler_settings' => $this->entityTypeManager()->getStorage('geocoder_provider')->load('maptiler')->get('configuration'),
              ],
            ],
            'sas_environment' => $this->settings->get('sas_environment'),
          ],
        ],
      ],
    ];
  }

  /**
   * Get render array of and error message to display in search page.
   *
   * @param string $message
   *
   * @return array
   *   Render array to display error message.
   */
  protected function getErrorRender(string $message): array {
    return [
      'content' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'sas-search-wrapper',
        ],
        'error_wrapper' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => 'noresult-wrapper',
          ],
          'error_block' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => 'noresult-block',
            ],
            'error_message' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $message,
              '#attributes' => [
                'class' => 'noresult-intro',
              ],
            ],
          ],
        ],
        '#cache' => [
          'max-age' => 3600,
          'contexts' => [
            'url.query_args:origin',
            'url.query_args:practitioner',
            'url.query_args:speciality',
            'url.query_args:streetnumber',
            'url.query_args:streetname',
            'url.query_args:inseecode',
            'url.query_args:city',
          ],
        ],
      ],
    ];
  }

}
