<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SasProcessorBase.
 *
 * Provide processor base for SAS processors.
 *
 * @package Drupal\sas_search\Plugin\search_api\processor
 */
abstract class SasProcessorBase extends ProcessorPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected $snpContentHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->entityTypeManager = $container->get('entity_type.manager');
    $processor->snpContentHelper = $container->get('sas_snp.content_helper');
    return $processor;
  }

}
