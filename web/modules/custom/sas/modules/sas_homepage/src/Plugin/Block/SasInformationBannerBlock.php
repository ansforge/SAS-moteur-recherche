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
 * Provides a sas information banner block.
 *
 * @Block(
 *   id = "sas_information_banner",
 *   admin_label = @Translation("SAS Information Banner"),
 *   category = @Translation("SAS")
 * )
 */
class SasInformationBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasApiClientService;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * SasInformationBannerBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ClientEndpointPluginManager $sas_api_client_service,
    AccountProxyInterface $current_user,
    SasKeycloakPscUserInterface $psc_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sasApiClientService = $sas_api_client_service;
    $this->currentUser = $current_user;
    $this->pscUser = $psc_user;
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
      $container->get('sas_api_client.service'),
      $container->get('current_user'),
      $container->get('sas_keycloak.psc_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    // Access forbidden if not authenticated as Drupal or PSC user.
    $access = AccessResult::allowedIf($this->currentUser->isAuthenticated() || $this->pscUser->isValid());

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
    $today = strtotime(date('Y-m-d'));
    $start_date = strtotime($api_values['information']['start_date']) ?? '';
    $end_date = strtotime($api_values['information']['end_date']) ?? '';

    $build['content'] = [
      '#theme' => 'sas_information_banner',
      '#message' => NULL,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    if (!empty($start_date) && !empty($end_date) && $today >= $start_date && $today <= $end_date) {
      $build['content']['#message'] = $api_values['information']['texte']['value'] ?? NULL;
    }

    return $build;
  }

}
