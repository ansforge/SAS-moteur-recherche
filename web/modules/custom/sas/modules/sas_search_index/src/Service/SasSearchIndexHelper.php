<?php

namespace Drupal\sas_search_index\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class SasSearchIndexHelper.
 *
 * Provide helpers to manage content indexation.
 *
 * @package Drupal\sas_search_index\Service
 */
class SasSearchIndexHelper implements SasSearchIndexHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $logger;

  /**
   * SasSearchIndexHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public function indexSpecificItem(int $nid) {
    // Get health_offer index.
    $index = $this->entityTypeManager->getStorage('search_api_index')->load('health_offer');
    if (empty($index)) {
      $this->logger->get('Sas Search Index')->error('Index "health_offer" not found.');
      return;
    }

    // Load item by id.
    $language = 'und';
    $item = $index->loadItem('entity:node/' . $nid . ':' . $language);
    if (!$item) {
      $language = 'fr';
      $item = $index->loadItem('entity:node/' . $nid . ':' . $language);
    }

    if (empty($item)) {
      $this->logger->get('Sas Search Index')->error('Item not found for content with id @id.', ['@id' => $nid]);
      return;
    }

    // Make item indexation.
    $index->indexSpecificItems(['entity:node/' . $nid . ':' . $language => $item]);
  }

}
