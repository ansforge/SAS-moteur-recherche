<?php

namespace Drupal\sas_homepage\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a sas information banner offline block.
 *
 * @Block(
 *   id = "sas_information_banner_offline",
 *   admin_label = @Translation("SAS Information Banner Offline"),
 *   category = @Translation("SAS")
 * )
 */
class SasInformationBannerOfflineBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClientService;

  /**
   * SasInformationBannerOfflineBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SasKeycloakPscUserInterface $psc_user,
    ClientEndpointPluginManager $sas_api_client_service,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pscUser = $psc_user;
    $this->sasApiClientService = $sas_api_client_service;
    $this->currentUser = $current_user;
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
      $container->get('sas_keycloak.psc_user'),
      $container->get('sas_api_client.service'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Access forbidden if authenticated as Drupal or PSC user.
    $access = AccessResult::allowedIf($this->currentUser->isAnonymous() && !$this->pscUser->isValid());

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $api_data = $this->sasApiClientService->sas_api_config('config', [
      'tokens' => ['id' => 'homepage'],
    ]);
    $api_values = $api_data['value'] ?? [];
    $enabled = $api_values['offline']['enabled'] ?? FALSE;

    $build['content'] = [
      '#theme' => 'sas_information_banner_offline',
      '#message' => NULL,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    if ($enabled) {
      $build['content']['#message'] = $api_values['offline']['texte']['value'] ?? NULL;
    }

    return $build;
  }

}
