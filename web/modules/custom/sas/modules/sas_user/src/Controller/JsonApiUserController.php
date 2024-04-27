<?php

namespace Drupal\sas_user\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_user\Service\SasUserHelperInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for SAS user routes.
 */
class JsonApiUserController extends ControllerBase {

  protected const JSON_CURRENT_USER_FIELDS = [
    'email' => 'mail',
    'firstname' => 'field_sas_prenom',
    'lastname' => 'field_sas_nom',
    'rpps_adeli' => 'field_sas_rpps_adeli',
  ];

  protected const JSON_CURRENT_USER_TERM_FIELDS_NAME = [
    'county' => 'field_sas_departement',
    'region' => 'field_region',
    'city' => 'field_ville',
  ];

  /**
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $userHelper;

  public function __construct(SasUserHelperInterface $user_helper) {
    $this->userHelper = $user_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_user.helper')
    );
  }

  /**
   * Get the current user info as JSON.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   The current user data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function apiCurrentUser() {

    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $cacheableMetadata->addCacheContexts(['user']);
    $response->addCacheableDependency($cacheableMetadata);
    $data = [
      'email' => '',
      'firstname' => '',
      'lastname' => '',
      'rpps_adeli' => '',
      'city' => '',
      'county' => '',
      'county_number' => '',
      'territory' => [],
      'territory_tid' => [],
      'territory_api_id' => [],
      'region' => '',
      'is_sas' => FALSE,
      'current_user_timezone' => date_default_timezone_get(),
      'roles' => array_values(
        preg_filter(
          '/^sas_(.*)$/',
          '$0',
          $this->currentUser()->getRoles()
        )
      ),
      // @todo Peut-être à supprimer : _user_is_logged_in pour la route.
      'user_is_logged_in' => $this->currentUser()->isAuthenticated(),
    ];

    $uid = $this->currentUser()->id();
    $user = $this->entityTypeManager()->getStorage('user')->load($uid);

    if ($user instanceof UserInterface) {

      $timezone = $this->userHelper->getUserRegionTimezone($user, TRUE);
      if (!empty($timezone)) {
        $data['current_user_timezone'] = $timezone;
      }

      foreach (static::JSON_CURRENT_USER_FIELDS as $key => $field_name) {
        if (!$user->get($field_name)->isEmpty()) {
          $data[$key] = $user->get($field_name)->value;
        }
      }

      foreach (static::JSON_CURRENT_USER_TERM_FIELDS_NAME as $key => $field_name) {
        if (!$user->get($field_name)->isEmpty()) {
          $terms = $user->get($field_name)->referencedEntities();
          if (!empty($terms)) {
            $term = reset($terms);
            $data[$key] = $term->getName();
          }
        }
      }

      if (!$user->get('field_sas_territoire')->isEmpty()) {
        $terms = $user->get('field_sas_territoire')->referencedEntities();
        if (!empty($terms)) {
          $data['territory_tid'] = array_column($user->get('field_sas_territoire')
            ->getValue(), 'target_id');
          $data['territory'] = array_map(
            fn($term) => [$term->id() => $term->getName()],
            $terms
          );
          foreach ($terms as $term) {
            /** @var \Drupal\taxonomy\TermInterface $term */
            if ($term->hasField('field_sas_api_id_territory') && !$term->get('field_sas_api_id_territory')->isEmpty()) {
              $data['territory_api_id'][] = $term->get('field_sas_api_id_territory')->value;
            }
          }
        }

      }

      if (!$user->get('field_sas_departement')->isEmpty() && $term = $user->get('field_sas_departement')->entity) {
        $data['county_number'] = $term->get('field_department_id')->value;
      }

      $data['is_sas'] = !empty($user->get('field_sas_user_sas')->value ?? NULL);
    }

    $response->setData($data);

    return $response;
  }

  /**
   * Get all sas roles names as JSON.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   All sas roles.
   */
  public function apiUserRoles() {

    $response = new CacheableJsonResponse();
    $cacheableMetadata = new CacheableMetadata();
    $allRoles = $this->entityTypeManager()->getStorage('user_role')->loadMultiple();
    $cacheableMetadata->addCacheableDependency($allRoles);
    $response->addCacheableDependency($cacheableMetadata);
    $data = [];
    foreach ($allRoles as $machineName => $role) {
      if (preg_match('/^sas_(.*)$/', $machineName)) {
        $data[$machineName] = $role->get('label');
      }
    }
    $response->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $response->setData($data);

    return $response;
  }

}
