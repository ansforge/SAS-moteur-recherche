<?php

namespace Drupal\sas_user_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for dashboard user sas page.
 */
class SasUserDashboard extends ControllerBase {

  /**
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $effectorHelper;

  public function __construct(
    SasEffectorHelperInterface $effector_helper
  ) {
    $this->effectorHelper = $effector_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_user.effector_helper')
    );
  }

  /**
   * Return markup for sas user dashboard.
   */
  public function render(Request $request): array {
    $isOwner = TRUE;

    if (
      !empty($userId = $request->get('userId')) &&
      $userId != $this->effectorHelper->getCurrentUserNationalId()
    ) {
      $isOwner = FALSE;
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'sas-dashboard-wrapper',
      ],
      '#attached' => [
        'library' => [
          'sas_vuejs/user-dashboard-page',
        ],
        'drupalSettings' => [
          'sas_vuejs' => [
            'isDashboardOwner' => $isOwner,
          ],
        ],
      ],
    ];
  }

}
