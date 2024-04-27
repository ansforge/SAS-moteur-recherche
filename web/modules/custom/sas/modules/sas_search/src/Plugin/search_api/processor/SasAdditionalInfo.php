<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Sas vacation processor.
 *
 * @SearchApiProcessor(
 *   id = "sas_additional_info",
 *   label = @Translation("field field_sas_time_info property"),
 *   description = @Translation("Adds the Entity is in vacation to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasAdditionalInfo extends SasProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['sas_additional_info'] = new ProcessorProperty([
        'label' => $this->t('SAS - Additional information'),
        'description' => $this->t('Property for additional information stored in SAS - Plages Horaires.'),
        'type' => 'string',
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

    if (!$this->snpContentHelper->isSupportSasSnpEntity($entity)) {
      return;
    }

    $fields = $item->getFields();
    $fields_add_info = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_additional_info');
    $fields_add_info = reset($fields_add_info);
    $additional_info = '';

    /** @var \Drupal\node\NodeInterface $snpEntity */
    $snpEntity = $this->snpContentHelper->getChild($entity);

    // Set sas_vacation solr property value.
    if (!empty($snpEntity) && $snpEntity->hasField('field_sas_time_info')) {
      $additional_info = $snpEntity->get('field_sas_time_info')->value;
    }
    $fields_add_info->addValue($additional_info);
  }

}
