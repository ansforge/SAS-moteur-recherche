<?php

namespace Drupal\sas_user\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This block is used to display login submit button.
 *
 * @Block(
 *   id = "sas_logout_psc_button",
 *   admin_label = @Translation("SAS logout psc"),
 *   category = @Translation("SAS")
 * )
 */
class SasLogoutPsc extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->pscUser = $container->get('sas_keycloak.psc_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cacheableMetadata = new CacheableMetadata();

    if ($this->pscUser->isValid()) {
      $logout = Link::createFromRoute($this->t('Déconnexion'),
        'sas_keycloak.cps_logout', [], [
          'attributes' => [
            'class' => 'btn-outline btn-logout',
          ],
        ])->toString();
    }

    $build = [
      '#theme' => 'block_sas_logout_psc_button',
      '#logout' => $logout ?? '',
    ];

    $cacheableMetadata->setCacheMaxAge(0);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->pscUser->isValid()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden(
      "The 'access Block' permission is required."
    );
  }

}
