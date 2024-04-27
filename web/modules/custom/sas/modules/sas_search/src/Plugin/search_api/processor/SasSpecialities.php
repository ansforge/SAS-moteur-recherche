<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_search\SolrDataFormatterTrait;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the item's Entity has snp to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sas_specialities",
 *   label = @Translation("SAS - Specialities"),
 *   description = @Translation("Store specialities as inline text."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasSpecialities extends SasProcessorBase {

  use SolrDataFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['sas_specialities'] = new ProcessorProperty([
        'label' => $this->t('SAS - Specialities'),
        'description' => $this->t('Specialities stored as inline text.'),
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

    // Only for node and SAS supported entities.
    if (
      !$entity instanceof ProfessionnelDeSanteSas ||
      !$this->snpContentHelper->isSupportSasSnpEntity($entity)
    ) {
      return;
    }
    $posSpecialitiesTid = $entity->getPosSpecialities('tid');
    $fields = $item->getFields();
    $fields_specialities = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_specialities');
    $field_specialities = reset($fields_specialities);
    $specialities = $this->formatSpecialityIdsAsString(array_unique($posSpecialitiesTid));
    $field_specialities->addValue($specialities);
  }

}
