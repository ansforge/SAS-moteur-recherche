<?php

namespace Drupal\sas_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\sas_core\SasCoreService;
use Symfony\Component\Routing\RouteCollection;

/**
 * SAS User Route subscriber to restrict user.register route.
 */
class SasUserRouteSubscriber extends RouteSubscriberBase {

  public const ACCESS_DENIED_ROUTES = [
    'user.register',
    'sante_user.password_reset',
    'sante_user.password_forgotten',
  ];

  /**
   * @var \Drupal\sas_core\SasCoreService
   */
  protected SasCoreService $sasCoreService;

  /**
   * Construct specific user Route subscriber to restrict for SAS.
   *
   * @param \Drupal\sas_core\SasCoreService $sasCoreService
   */
  public function __construct(SasCoreService $sasCoreService) {
    $this->sasCoreService = $sasCoreService;
  }

  /**
   * {@inheritDoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (self::ACCESS_DENIED_ROUTES as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_sas_context_access_check', 'FALSE');
      }
    }

    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\sas_user\Form\SasUserLoginForm');
    }

    if ($route = $collection->get('sante_user.email_reset')) {
      $route->setDefault(
        '_form',
        '\Drupal\sas_user\Form\SasUserEmailResetForm'
      );
    }

    if ($route = $collection->get('sante_user.credentials')) {
      $route->setDefault('_form', '\Drupal\sas_user\Form\SasUserAccountForm');
    }
  }

}
