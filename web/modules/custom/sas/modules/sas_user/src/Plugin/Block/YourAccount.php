<?php

namespace Drupal\sas_user\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\SnpService;
use Drupal\sas_user\Service\SasUserHelperInterface;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'YourAccount' block.
 *
 * @Block(
 *  id = "your_account_block",
 *  admin_label = @Translation("Your account sas block"),
 *  category="SAS account",
 * )
 */
class YourAccount extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * The route match.
   *
   * @var \Drupal\sas_snp\SnpService
   */
  protected SnpService $sasSnpManager;

  /**
   * DashboardUser service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * DashboardUser service.
   *
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $userHelper;

  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasService;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->sasSnpManager = $container->get('sas_snp.manager');
    $instance->dashboard = $container->get('sas_user_dashboard.dashboard');
    $instance->accountProxy = $container->get('current_user');
    $instance->userHelper = $container->get('sas_user.helper');
    $instance->sasService = $container->get('sas_core.service');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $user = $this->accountProxy;
    $dashboard = $dashboard_gestionnaire = $dashboard_delegataire = $adding_delegataire = '';
    $roles = [];
    $results = [];

    foreach ($user->getRoles(TRUE) as $role) {
      $label = Role::loadMultiple()[$role]->label();
      $roles[] = [
        'label' => $label,
      ];
    }

    $identifiant = Link::createFromRoute($this->t('Vos identifiants de connexion'),
      'sante_user.credentials', [], [
        'attributes' => [
          'class' => 'identifiant',
        ],
      ])->toRenderable();

    $user_storage = $this->entityTypeManager->getStorage('user')
      ->load($user->id());

    $user_last_name = '';
    $user_first_name = '';

    if (!$user_storage->get('field_sas_nom')->isEmpty()) {
      $user_last_name = $user_storage->get('field_sas_nom')
        ->first()
        ->getValue()['value'];
    }

    if (!$user_storage->get('field_sas_prenom')->isEmpty()) {
      $user_first_name = $user_storage->get('field_sas_prenom')
        ->first()
        ->getValue()['value'];
    }

    $logout = Link::createFromRoute($this->t('Vous déconnecter'),
      'user.logout', [], [
        'attributes' => [
          'class' => 'logout',
        ],
      ])->toRenderable();

    if (in_array(SnpConstant::SAS_EFFECTEUR, $user->getRoles())) {
      $dashboard = Link::createFromRoute($this->t('Mon espace personnel'),
        'sas_user_dashboard.root', [], [
          'attributes' => [
            'class' => 'places-consultation',
          ],
        ])->toRenderable();
    }

    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $user->getRoles())) {
      $dashboard_gestionnaire = Link::createFromRoute($this->t('Vos structures liées'),
        'sas_user_dashboard.gestionnaire_de_structure', ['user' => $user->id()], [
          'attributes' => [
            'class' => 'structures',
          ],
        ])->toRenderable();
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $user->getRoles())) {
      $dashboard_delegataire = Link::createFromRoute($this->t('Votre espace de délégation'),
        'sas_user_dashboard.delegataire', ['user' => $user->id()], [
          'attributes' => [
            'class' => 'delegation-space',
          ],
        ])->toRenderable();
    }

    if (in_array(SnpConstant::SAS_EFFECTEUR, $user->getRoles())
      || in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $user->getRoles())) {

      $delegate_users = $this->userHelper->getSasUserRelatedDelegataire($user->id());
      $results = [];
      foreach ($delegate_users as $delegate_user) {
        $user_related = $this->entityTypeManager->getStorage('user')
          ->load($delegate_user);

        if (!$user_related->get('field_sas_nom')->isEmpty()) {
          $last_name = $user_related->get('field_sas_nom')
            ->first()
            ->getValue()['value'];
        }

        if (!$user_related->get('field_sas_prenom')->isEmpty()) {
          $first_name = $user_related->get('field_sas_prenom')
            ->first()
            ->getValue()['value'];
        }
        $results[] = [
          'last_name' => $last_name ?? '',
          'first_name' => $first_name ?? '',
        ];
      }

      $adding_delegataire = Link::createFromRoute($this->t("Demander l'ajout d'un délégataire"),
        'sas_user.adding_delegataire', [], [
          'attributes' => [
            'class' => 'delegates-btn',
          ],
        ])->toRenderable();
    }

    return [
      '#theme' => 'your_account_block',
      '#identifiant' => $identifiant,
      '#user_last_name' => $user_last_name,
      '#user_first_name' => $user_first_name,
      '#logout' => $logout,
      '#roles' => $roles,
      '#dashboard' => $dashboard,
      '#dashboard_gestionnaire' => $dashboard_gestionnaire,
      '#dashboard_delegataire' => $dashboard_delegataire,
      '#results' => $results,
      '#adding_delegataire' => $adding_delegataire,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->sasService->isSasContext() && $account->isAuthenticated()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
