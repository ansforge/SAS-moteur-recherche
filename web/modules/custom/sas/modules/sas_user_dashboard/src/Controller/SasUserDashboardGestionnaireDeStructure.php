<?php

namespace Drupal\sas_user_dashboard\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Service\CptsHelperInterface;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for dashboard user sas page.
 */
class SasUserDashboardGestionnaireDeStructure extends ControllerBase {

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * Cpts service.
   *
   * @var \Drupal\sas_structure\Service\CptsHelperInterface
   */
  protected CptsHelperInterface $cptsHelper;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a BookNodeIsRemovableAccessCheck object.
   *
   *   Book Manager Service.
   */
  public function __construct(
    DashboardUserInterface $dashboard,
    EntityTypeManager $entity,
    CurrentRouteMatch $currentRouteMatch,
    CptsHelperInterface $cptsHelper,
    CacheBackendInterface $cache
  ) {
    $this->dashboard = $dashboard;
    $this->userStorage = $entity->getStorage('user');
    $this->currentRouteMatch = $currentRouteMatch;
    $this->cptsHelper = $cptsHelper;
    $this->cache = $cache;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_user_dashboard.dashboard'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('sas_structure.cpts_helper'),
      $container->get('cache.data')
    );
  }

  /**
   * Return build for dashboard user sas page.
   */
  public function renderPageDashboard(): array {
    $cacheableMetadata = new CacheableMetadata();

    /** @var \Drupal\user\UserInterface $user */
    $user = in_array(SnpConstant::SAS_DELEGATAIRE, $this->currentUser()
      ->getRoles()) ? $this->userStorage->load($this->currentRouteMatch->getParameter('user')) : $this->userStorage->load($this->currentUser()->id());

    if (in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $this->currentUser()
      ->getRoles()) && $this->currentUser()
      ->id() == $this->currentRouteMatch->getParameter('user')) {
      $role = 'sas_gestionnaire_de_structure';
    }

    $results = [];
    if (!$user->get('field_sas_attach_structures')->isEmpty()) {
      $nodes = $user->get('field_sas_attach_structures')
        ->referencedEntities();

      $results = $this->dashboard->sasDashboardUser($nodes, $user);
    }

    // Get Sos Médecin Association and PFG.
    $association_list = [];
    if ($user->hasField(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)
        && !$user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->isEmpty()) {
      $siret_list = array_column($user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->getValue(), 'value');
      $association_list = $this->dashboard->getSosMedecinAssociations($siret_list, $user);
    }
    // Get the CPTS list for a Manager.
    $cache_key = sprintf('sas:cpts_list_dashboard:%d', $user->id());
    $cpts_list = $this->cptsHelper->getCptsListForUser($user);
    if (!empty($cpts_list)) {
      $this->cache->set($cache_key, $cpts_list, CacheBackendInterface::CACHE_PERMANENT, ['sas_cpts_list_dashboard']);
    }

    $sas_config = $this->config('sas_config.user_account')
      ->get('texts');
    $cacheableMetadata->addCacheableDependency($sas_config);
    if (!empty($sas_config)) {
      $general_info = $sas_config['dashboard']['site_mng']['general_info'];
      $paragraph = $sas_config['dashboard']['site_mng']['paragraph']['value'];
    }

    $build = [
      '#theme' => 'sas-user-dashboard-gestionnaire-de-structure',
      '#role' => $role ?? '',
      '#general_info' => $general_info ?? '',
      '#paragraph' => $paragraph ?? '',
      '#results' => $results,
      '#sos_medecin_assos' => $association_list,
      '#cpts_list' => $cpts_list,
    ];

    $cacheableMetadata->applyTo($build);
    return $build;
  }

}
