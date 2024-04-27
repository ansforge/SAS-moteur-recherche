<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's Entity has snp to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "has_snp",
 *   label = @Translation("SAS - Has SNP"),
 *   description = @Translation("Adds has snp property to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class HasSnp extends SasProcessorBase {

  /**
   * @var \Drupal\sas_snp\Manager\SasAvailabilityManager
   */
  protected $availabilityManager;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['sas_has_snp'] = new ProcessorProperty([
        'label' => $this->t('SAS - Has SNP'),
        'description' => $this->t('Entity has snp'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->availabilityManager = $container->get('sas_snp.availability_manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getEntity();

    // Only for node.
    if (!$entity instanceof Node) {
      return;
    }

    if (!$this->snpContentHelper->isSupportSasSnpEntity($entity)) {
      return;
    }

    $fields = $item->getFields();
    $fields_has_snp = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_has_snp');
    $field_has_snp = reset($fields_has_snp);
    $has_snp = FALSE;

    // Use the SasAvailabilityManager to load the value from the table.
    $availability = $this->availabilityManager->loadByNid($entity->id());

    if ($availability) {
      // Extract the "has_snp" value from the loaded availability.
      $has_snp = (bool) $availability->isHasSnp();
    }
    $field_has_snp->addValue((bool) $has_snp);
  }

}
