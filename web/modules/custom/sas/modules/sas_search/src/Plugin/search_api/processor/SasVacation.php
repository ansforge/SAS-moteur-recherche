<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Sas vacation processor.
 *
 * @SearchApiProcessor(
 *   id = "sas_vacation",
 *   label = @Translation("field sas_vacation property"),
 *   description = @Translation("Adds the Entity is in vacation to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasVacation extends SasProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['sas_vacation'] = new ProcessorProperty([
        'label' => $this->t('SAS - Is in vacation'),
        'description' => $this->t('Field for entity is in vacation'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);
    }

    for ($i = 0; $i < SnpConstant::SAS_MAX_VACATION_SLOT_NB; $i++) {
      $properties[sprintf('sas_vacation_slot_start_%d', $i)] = new ProcessorProperty([
        'label' => $this->t('SAS - Vacation slot - Start date :nb', [':nb' => $i + 1]),
        'description' => $this->t('Property to store vacation slot :nb start date', [':nb' => $i + 1]),
        'type' => 'integer',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);
      $properties[sprintf('sas_vacation_slot_end_%d', $i)] = new ProcessorProperty([
        'label' => $this->t('SAS - Vacation slot - End date :nb', [':nb' => $i + 1]),
        'description' => $this->t('Property to store vacation slot :nb end date', [':nb' => $i + 1]),
        'type' => 'integer',
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
    $fields_sas_vacation = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_vacation');
    $field_sas_vacation = reset($fields_sas_vacation);
    $sas_vacation = FALSE;

    /** @var \Drupal\node\NodeInterface $snpEntity */
    $snpEntity = $this->snpContentHelper->getChild($entity);

    // Set sas_vacation solr property value.
    if (!empty($snpEntity)) {
      $sas_vacation = $snpEntity->get('field_sas_time_snp_active')->value;
    }
    $field_sas_vacation->addValue((bool) $sas_vacation);

    // Set solr property value for each vacation timeslot.
    for ($i = 0; $i < SnpConstant::SAS_MAX_VACATION_SLOT_NB; $i++) {
      $fields_sas_vacation_start = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, sprintf('sas_vacation_slot_start_%d', $i));
      $fields_sas_vacation_end = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, sprintf('sas_vacation_slot_end_%d', $i));
      $field_sas_vacation_start = reset($fields_sas_vacation_start);
      $field_sas_vacation_end = reset($fields_sas_vacation_end);
      $vacation_start = $vacation_end = 0;

      if (!empty($snpEntity)) {
        $dates = $snpEntity->get('field_sas_time_slot_vacations')->getValue();
        if (!empty($dates[$i]) && isset($dates[$i]['value']) && isset($dates[$i]['end_value'])) {
          $vacation_start = strtotime($dates[$i]['value']);
          $vacation_end = strtotime($dates[$i]['end_value']);
        }
      }
      $field_sas_vacation_start->addValue($vacation_start);
      $field_sas_vacation_end->addValue($vacation_end);
    }
  }

}
