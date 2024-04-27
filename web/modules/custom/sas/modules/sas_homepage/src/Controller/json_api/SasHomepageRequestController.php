<?php

namespace Drupal\sas_homepage\Controller\json_api;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for SAS Homepage routes.
 */
class SasHomepageRequestController extends ControllerBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManager;
   */
  protected $themeManager;

  /**
   * The sas api service client.
   *
   * @var Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
   */
  protected $sasApiClientService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->themeManager = $container->get('theme.manager');
    $instance->sasApiClientService = $container->get('sas_api_client.service');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function getHomepageConfig() {
    $status = 200;
    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $api_data = $this->sasApiClientService->sas_api_config('config', [
      'tokens' => ['id' => 'homepage'],
    ]);
    $api_values = $api_data['value'] ?? [];
    $theme_src = $this->themeManager->getActiveTheme()->getPath();
    $cacheableMetadata->addCacheableDependency($this->currentUser()->isAuthenticated());
    $data = [
      'user_is_logged_in' => $this->currentUser()->isAuthenticated(),
      'hp_unlogged_user_text' => $api_values['texte_principal'] ?? '',
      'hp_unlogged_user_objectives' => $api_values['objectives'] ?? [],
      'hp_logged_user_text' => $api_values['text_header_connected'] ?? '',
      'hp_logged_user_text_1' => $api_values['text_header_connected_1'] ?? '',
    ];
    $bg_images = [
      'homepage_image' => $api_values['homepage_image'][0] ?? NULL,
      'homepage_image_connected_mobile' => $api_values['homepage_image_connected_mobile'][0] ?? NULL,
      'homepage_image_unlogged' => $api_values['homepage_image_unlogged'][0] ?? NULL,
    ];
    foreach ($bg_images as $key => $id) {
      $data['hp_' . $key] = Url::fromUri('internal:/' . $theme_src . '/images/home-img.jpg', ['absolute' => TRUE])->toString();
      if (empty($id)) {
        continue;
      }
      $bg_image = $this->entityTypeManager()
        ->getStorage('file')
        ->load($id);
      if ($bg_image instanceof FileInterface) {
        $cacheableMetadata->addCacheableDependency($bg_image);
        $data['hp_' . $key] = $bg_image->createFileUrl(FALSE);
      }
    }

    $cacheableMetadata->applyTo($data);
    $response->setData($data);
    $response->addCacheableDependency($cacheableMetadata);
    $response->setStatusCode($status);
    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $response;
  }

}
