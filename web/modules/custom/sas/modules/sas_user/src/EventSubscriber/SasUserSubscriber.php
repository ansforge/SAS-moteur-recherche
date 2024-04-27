<?php

namespace Drupal\sas_user\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\sas_core\SasCoreServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SasUserSubscriber to dispatch user requests.
 */
class SasUserSubscriber implements EventSubscriberInterface {

  /**
   * The SAS core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * The current Route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The Drupal current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\sas_core\SasCoreServiceInterface $sasCoreService
   *   The SAS core service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current routeMatch.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The current user interface.
   */
  public function __construct(SasCoreServiceInterface $sasCoreService,
                              CurrentRouteMatch $routeMatch,
                              AccountProxyInterface $accountProxy) {
    $this->sasCoreService = $sasCoreService;
    $this->routeMatch = $routeMatch;
    $this->currentUser = $accountProxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The current event object.
   */
  public function onRequest(RequestEvent $event) {
    // Manage some user pages for SAS context if necessary.
    if ($this->sasCoreService->isSasContext()) {
      $url = NULL;
      switch ($this->routeMatch->getRouteName()) {
        // Redirect user creation in BO for SAS.
        case 'user.admin_create':
          $url = Url::fromRoute('sas_user.admin_create');
          break;
      }

      if ($url) {
        $response = new RedirectResponse($url->toString(), 302);
        $event->setResponse($response);
      }
    }
  }

}
