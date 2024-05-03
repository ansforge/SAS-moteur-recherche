<?php

declare(strict_types = 1);

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\sas_directory_pages\Entity\HealthInstitutionSas;
use Drupal\sas_structure\Service\CptsHelper;
use Drupal\sas_structure\Service\StructureHelper;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's Entity participate sas to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sas_cpts_phone",
 *   label = @Translation("SAS - CPTS phone."),
 *   description = @Translation("Adds phone from care deal into CPTS content."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasCptsPhone extends SasProcessorBase {

  /**
   * @var \Drupal\sas_structure\Service\StructureHelper|null
   */
  protected ?StructureHelper $structureHelper;

  /**
   * @var \Drupal\sas_structure\Service\CptsHelper|null
   */
  protected ?CptsHelper $cptsHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->structureHelper = $container->get('sas_structure.helper');
    $processor->cptsHelper = $container->get('sas_structure.cpts_helper');

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      // BEGIN-NOSCAN.
      $properties['sas_cpts_care_deal_phones'] = new ProcessorProperty([
        'label' => $this->t('SAS - CPTS phone numbers from Care Deal.'),
        'description' => $this->t('Property solr to store list of phone number indexed from linked care deals'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ]);
      // END-NOSCAN.
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item): void {
    $entity = $item->getOriginalObject()->getEntity();

    // Only for node CPTS of type Health_institution.
    if (!$entity instanceof HealthInstitutionSas || !$this->structureHelper->isCpts($entity)) {
      return;
    }

    $fields = $item->getFields();
    $fields_sas_cpts_phones = $this->getFieldsHelper()->filterForPropertyPath(
      fields: $fields,
      datasource_id: NULL,
      property_path: 'sas_cpts_care_deal_phones'
    );
    $fields_sas_cpts_phones = reset($fields_sas_cpts_phones);

    $cpts_phones = $this->cptsHelper->getCptsCareDealPhones($entity);
    if (!empty($cpts_phones)) {
      foreach ($cpts_phones as $cpts_phone) {
        $fields_sas_cpts_phones->addValue($cpts_phone);
      }
    }
  }

}
