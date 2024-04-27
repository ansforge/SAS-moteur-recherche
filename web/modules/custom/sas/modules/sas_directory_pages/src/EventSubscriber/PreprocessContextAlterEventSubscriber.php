<?php

namespace Drupal\sas_directory_pages\EventSubscriber;

use Drupal\sante_directory_pages\Event\PreprocessContextAlterEvent;
use Drupal\sas_core\SasCoreServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PreprocessContextAlterEventSubscriber.
 * Subscribe to the PreprocessContextAlterEvent
 * and alter the current preprocess business context.
 *
 * @package Drupal\sas_directory_pages\EventSubscriber
 */
class PreprocessContextAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * The sas core context service.
   *
   * @var Drupal\sas_core\SasCoreServiceInterface
   */
  private $sasCoreService;

  /**
   * PreprocessService constructor.
   *
   * @param \Drupal\sas_core\SasCoreServiceInterface $sasCoreService
   */
  public function __construct(
    SasCoreServiceInterface $sasCoreService
  ) {
    $this->sasCoreService = $sasCoreService;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      PreprocessContextAlterEvent::EVENT_NAME => 'onPreprocessContext',
    ];
  }

  /**
   * Subscribe to the preprocess business context event
   * and alter it if we are in the SAS context.
   *
   * @param \Drupal\sante_directory_pages\Event\PreprocessContextAlterEvent $event
   *   The preprocess business context event.
   */
  public function onPreprocessContext(PreprocessContextAlterEvent $event) {
    if ($this->sasCoreService->isSasContext()) {
      $event->businessContext = 'sas';
    }
  }

}
