<?php

namespace Drupal\sas_user\Plugin\Block;

use Drupal\ans_openid_connect\Form\AnsOpenidConnectLoginForm;
use Drupal\ans_openid_connect\Services\AnsOpenidConnectClientInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_keycloak\Service\SasKeycloakManager;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This block is used to display login submit button.
 *
 * @Block(
 *   id = "sas_login_button",
 *   admin_label = @Translation("SAS Login"),
 *   category = @Translation("SAS")
 * )
 */
class SasLogin extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The ANS OpenID Connect client helper.
   *
   * @var \Drupal\ans_openid_connect\Services\AnsOpenidConnectClientInterface
   */
  protected AnsOpenidConnectClientInterface $ansOpenidConnectClient;

  /**
   * The SAS Keycloak Manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakManager
   */
  protected SasKeycloakManager $sasKeycloakManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->formBuilder = $container->get('form_builder');
    $instance->ansOpenidConnectClient = $container->get('ans_openid_connect.client');
    $instance->sasKeycloakManager = $container->get('sas_keycloak.manager');
    $instance->configFactory = $container->get('config.factory');
    $instance->pscUser = $container->get('sas_keycloak.psc_user');
    $instance->sasCoreService = $container->get('sas_core.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cacheableMetadata = new CacheableMetadata();

    $openid_config = $this->configFactory->get('openid_connect.settings.sas');
    $keycloak_config = $this->configFactory->get('sas_keycloak.features');
    $cacheableMetadata->addCacheableDependency($openid_config);
    $cacheableMetadata->addCacheableDependency($keycloak_config);
    $ansOpenidConnectClient = $this->sasCoreService->isSasBoContext() ? 'sas-bo' : 'sas';

    if ($this->sasKeycloakManager->isFeatureEnabled('connect')
      && $this->ansOpenidConnectClient->isEnabled($ansOpenidConnectClient)) {
      $login_form = $this->formBuilder->getForm(
        AnsOpenidConnectLoginForm::class,
        $ansOpenidConnectClient,
        'Se connecter',
        'sas-icon sas-icon-connection'
      );
      $class = "";
    }
    else {
      $login_form = Link::createFromRoute('Se connecter', 'user.login');
      $class = "account-panel-opener";
    }

    $build = [
      '#theme' => 'block_sas_login_button',
      '#login_link' => $login_form,
      '#class' => $class,
    ];

    $cacheableMetadata->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Hide the block for specific user psc.
    if ($this->pscUser->isValid()) {
      // @todo ajouter du cache, et un cache custom context psc
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
