<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's is_sas_entity property to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sas_is_sas_entity",
 *   label = @Translation("SAS - field is_sas_entity property"),
 *   description = @Translation("Adds the is_sas_entity property to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class IsSasEntity extends SasProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['is_sas_entity'] = new ProcessorProperty([
        'label' => $this->t('SAS - Is Sas Entity'),
        'description' => $this->t('Field Is Sas Entity'),
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
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getEntity();

    // Only for node.
    if (!$entity instanceof Node) {
      return;
    }

    $fields = $item->getFields();
    $fields_is_sas_entity = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'is_sas_entity');
    $field_is_sas_entity = reset($fields_is_sas_entity);

    $field_is_sas_entity->addValue($this->snpContentHelper->isSupportSasSnpEntity($entity));
  }

}
