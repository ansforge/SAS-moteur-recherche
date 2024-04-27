<?php

namespace Drupal\sas_user_dashboard\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for dashboard user sas page.
 */
class SasUserDashboardDelegataire extends ControllerBase {

  /**
   * Constructs a SasUserDashboardEffecteur object.
   *
   *   SasUserDashboardEffecteur Manager Service.
   */
  public function __construct(EntityTypeManager $entity) {
    $this->userStorage = $entity->getStorage('user');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Return build for dashboard user sas page.
   */
  public function renderPageDashboard(): array {
    $cacheableMetadata = new CacheableMetadata();
    $user = $this->userStorage->load($this->currentUser()->id());
    $cacheableMetadata->addCacheableDependency($user);

    $sheets = [
      'structure' => 'field_sas_rel_structure_manager',
      'professionals' => 'field_sas_related_pro',
    ];

    $result = [];
    foreach ($sheets as $key => $sheet) {
      if (!$user->get($sheet)->isEmpty()) {
        $managers = $user->get($sheet)
          ->referencedEntities();

        foreach ($managers as $manager) {
          $cacheableMetadata->addCacheableDependency($manager);

          $id = $key === 'professionals' ? $manager->field_sas_rpps_adeli->value : $manager->id();

          if (!$manager->get('field_sas_nom')->isEmpty()) {
            $last_name = $manager->get('field_sas_nom')
              ->first()
              ->getValue()['value'];
          }

          if (!$manager->get('field_sas_prenom')->isEmpty()) {
            $first_name = $manager->get('field_sas_prenom')
              ->first()
              ->getValue()['value'];
          }

          if (!$manager->get('mail')->isEmpty()) {
            $email = $manager->get('mail')
              ->first()
              ->getValue()['value'];
          }
          $result[$key][] = [
            'id' => $id,
            'last_name' => $last_name ?? '',
            'first_name' => $first_name ?? '',
            'email' => $email ?? '',
          ];
        }
      }
    }

    $sas_config = $this->config('sas_config.user_account')
      ->get('texts');
    $cacheableMetadata->addCacheableDependency($sas_config);

    $general_info = $paragraph = '';

    if (!empty($sas_config)) {
      $general_info = $sas_config['dashboard']['delegate']['general_info'];
      $paragraph = $sas_config['dashboard']['delegate']['paragraph']['value'];
    }

    if (!$user->get('field_sas_nom')->isEmpty()) {
      $last_name = $user->get('field_sas_nom')->first()->getValue()['value'];
    }
    if (!$user->get('field_sas_prenom')->isEmpty()) {
      $first_name = $user->get('field_sas_prenom')->first()->getValue()['value'];
    }
    if (!$user->get('mail')->isEmpty()) {
      $email = $user->get('mail')->first()->getValue()['value'];
    }

    $build = [
      '#theme' => 'sas-user-dashboard-delegataire',
      '#general_info' => $general_info,
      '#paragraph' => $paragraph,
      '#last_name' => $last_name ?? '',
      '#first_name' => $first_name ?? '',
      '#email' => $email ?? '',
      '#structures' => $result['structure'] ?? [],
      '#professionals' => $result['professionals'] ?? [],
    ];

    $cacheableMetadata->applyTo($build);
    return $build;
  }

}
