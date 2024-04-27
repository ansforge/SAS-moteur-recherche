<?php

namespace Drupal\sas_user_dashboard\EventSubscriber;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\sas_user_settings\Service\SasUserSettingsHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SasUserDashboardSybscriber to dispatch dashboard requests.
 */
class SasUserDashboardSubscriber implements EventSubscriberInterface {
  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * Drupal current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $routeMatch;

  /**
   * AccountProxy object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $accountProxy;

  /**
   * The path matcher interface.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected PathMatcherInterface $pathMatcher;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * The SAS cgu service.
   *
   * @var \Drupal\sas_user_settings\Service\SasUserSettingsHelperInterface
   */
  protected SasUserSettingsHelperInterface $sasUserSettingsHelper;

  /**
   * The sasEffector helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * Constructor SubscribedEvents.
   */
  public function __construct(SasCoreServiceInterface $sasCoreService,
                              AccountProxyInterface $accountProxy,
                              CurrentRouteMatch $routeMatch,
                              PathMatcherInterface $pathMatcher,
                              SasKeycloakPscUserInterface $psc_user,
                              SasEffectorHelperInterface $sasEffectorHelper,
                              SasUserSettingsHelperInterface $sasUserSettingsHelper) {
    $this->sasCoreService = $sasCoreService;
    $this->accountProxy = $accountProxy;
    $this->routeMatch = $routeMatch;
    $this->pathMatcher = $pathMatcher;
    $this->pscUser = $psc_user;
    $this->sasEffectorHelper = $sasEffectorHelper;
    $this->sasUserSettingsHelper = $sasUserSettingsHelper;
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['checkFrontRedirection', 29],
    ];
  }

  public function checkFrontRedirection(RequestEvent $event) {
    // Ignore if not SAS context.
    if (!$this->sasCoreService->isSasContext()) {
      return;
    }

    $rpps_adeli = in_array(SasUserConstants::SAS_EFFECTOR_ROLE, $this->accountProxy->getRoles())
      ? $this->sasEffectorHelper->getRppsAdeliInUserId($this->accountProxy->id())
      : $this->accountProxy->id();
    $user_id = $this->pscUser->isValid() ? $this->pscUser->getCurrentUser()->get('id') : $rpps_adeli;

    // Skip redirection if user don't have CGU. User will be redirected to CGU page.
    if (empty($this->sasUserSettingsHelper->getUserCgu($user_id))) {
      return;
    }

    if ($this->pathMatcher->isFrontPage() &&
      ($this->accountProxy->isAuthenticated() || $this->pscUser->isValid()) &&
      empty(array_intersect($this->accountProxy->getRoles(), [
        SasUserConstants::SAS_ADMIN_ROLE,
        SasUserConstants::SAS_ADMIN_NAT_ROLE,
        SasUserConstants::SAS_ACCOUNT_MANAGER_ROLE,
        SasUserConstants::SAS_REGULATOR_OSNP_ROLE,
        SasUserConstants::SAS_IOA_ROLE,
        SasUserConstants::SAS_TEST_EDITEUR_LRM,
      ]))) {

      $redirect = $this->redirectEventResponse();
      $response = new RedirectResponse($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function redirectEventResponse() {
    $redirect = Url::fromRoute('<front>')->toString();

    if (in_array(SasUserConstants::SAS_DELEGATE_ROLE, $this->accountProxy->getRoles())) {
      $redirect = Url::fromRoute('sas_user_dashboard.delegataire',
        ['user' => $this->accountProxy->id()])->toString();
    }

    if (in_array(SasUserConstants::SAS_STRUCT_MANAGER_ROLE, $this->accountProxy->getRoles())) {
      $redirect = Url::fromRoute('sas_user_dashboard.gestionnaire_de_structure',
        ['user' => $this->accountProxy->id()])->toString();
    }

    if (
      in_array(SasUserConstants::SAS_EFFECTOR_ROLE, $this->accountProxy->getRoles()) ||
      $this->pscUser->isValid()
    ) {
      $redirect = Url::fromRoute('sas_user_dashboard.root')->toString();
    }

    return $redirect;
  }

}
