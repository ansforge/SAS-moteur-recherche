<?php

namespace Drupal\sas_snp\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Service\SnpContentHelperInterface;
use Drupal\sas_snp\SnpService;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'AvailabilityBlock' block.
 *
 * @Block(
 *  id = "availability_link_block",
 *  admin_label = @Translation("Availability block"),
 * )
 */
class AvailabilityBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The route match.
   *
   * @var \Drupal\sas_snp\SnpService
   */
  protected $sasSnpManager;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * If content type allowed for SNP.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected SnpContentHelperInterface $snpContentHelper;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('sas_snp.manager'),
      $container->get('sas_user_dashboard.dashboard'),
      $container->get('current_user'),
      $container->get('sas_keycloak.psc_user'),
      $container->get('path.current'),
      $container->get('sas_snp.content_helper'),
      $container->get('sas_user.effector_helper')
    );
  }

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\sas_snp\SnpService $sas_snp_manager
   * @param \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $psc_user
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   * @param \Drupal\sas_snp\Service\SnpContentHelperInterface $snpContentHelper
   * @param \Drupal\sas_user\Service\SasEffectorHelperInterface $sasEffectorHelper
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
    RouteMatchInterface $route_match,
    EntityTypeManager $entity_type_manager,
    SnpService $sas_snp_manager,
    DashboardUserInterface $dashboard,
    AccountProxyInterface $accountProxy,
    SasKeycloakPscUserInterface $psc_user,
    CurrentPathStack $current_path,
    SnpContentHelperInterface $snpContentHelper,
    SasEffectorHelperInterface $sasEffectorHelper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->sasSnpManager = $sas_snp_manager;
    $this->dashboard = $dashboard;
    $this->accountProxy = $accountProxy;
    $this->pscUser = $psc_user;
    $this->currentPath = $current_path;
    $this->snpContentHelper = $snpContentHelper;
    $this->sasEffectorHelper = $sasEffectorHelper;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $user = $this->accountProxy;
    if ($this->pscUser->isValid()) {
      $datas = $this->dashboard->sasGetEntitySasSnpUserData($this->pscUser->get('id'));
    }
    else {
      $rpps_adeli = $this->sasEffectorHelper->getRppsAdeliInUserId($user->id());
      $datas = $this->dashboard->sasGetEntitySasSnpUserData($rpps_adeli);
    }

    if (in_array(SnpConstant::SAS_DELEGATAIRE, $user->getRoles())) {
      $user_pro = $this->entityTypeManager->getStorage('user')->load($user->id());
      $managers = $user_pro->get('field_sas_related_pro')->referencedEntities();
      if (!empty($managers)) {
        $managers = reset($managers);
        $rpps_adeli = $this->sasEffectorHelper->getRppsAdeliInUserId($managers->id());
        $datas = $this->dashboard->sasGetEntitySasSnpUserData($rpps_adeli);
      }
    }

    $node = $this->configuration['node'];
    $url = '';
    if ($node instanceof NodeInterface) {
      $url = $node->toUrl(
        'sas-snp-availability',
        [
          'node' => $node->id(),
          'query' => [
            'back_url' => $this->currentPath->getPath(),
          ],
        ]
      );
    }

    $class = '';
    if ($node->bundle() == SnpConstant::PROFESSIONNEL_SANTE ||
      $this->pscUser->isValid()) {
      if (empty($datas) || ($datas->get('editor_disabled')
        ->first()
        ->getValue()['value'] == FALSE && !array_intersect($user->getRoles(), [
          SnpConstant::SAS_ADMINISTRATEUR,
          SnpConstant::SAS_ADMINISTRATEUR_NATIONAL,
        ])) || !$this->snpContentHelper->isSupportSasSnpEntity($node)
        || ($datas->get('participation_sas')
          ->first()
          ->getValue()['value'] == TRUE
          && $datas->get('participation_sas_via')
            ->first()
            ->getValue()['value'] == SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN)) {

        $class = 'is-disabled';
        $url = '#';
      }
    }

    return [
      '#theme' => 'availability-block',
      '#link' => $url,
      '#class' => $class,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->configuration['node'];
    return $node instanceof NodeInterface ? Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]) : parent::getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->configuration['node'];
    if ($account->hasPermission('bypass node access')) {
      return AccessResult::allowed();
    }

    if (in_array(SnpConstant::SAS_ADMINISTRATEUR, $account->getRoles())
      || in_array(SnpConstant::SAS_ADMINISTRATEUR_NATIONAL, $account->getRoles())) {
      return AccessResult::allowed();
    }

    if ($this->pscUser->isValid()) {
      return AccessResult::allowed();
    }

    $node_ids = $this->sasSnpManager->getSnpNodesIds($account);

    if (!in_array($node->id(), $node_ids)) {
      return AccessResult::forbidden("The 'access content' permission is required.")->cachePerUser();
    }
    return AccessResult::allowed();
  }

}
