<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Territory processor (ids and labels) for node entities.
 *
 * @SearchApiProcessor(
 *   id = "sas_territory",
 *   label = @Translation("SAS - Territory linked to the entity"),
 *   description = @Translation("Manage territories linked to entities."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasTerritory extends SasProcessorBase {

  /**
   * Territory term helper.
   *
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected $territoryHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->territoryHelper = $container->get('term.territory');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if (!$datasource) {
      // Territory IDs.
      $properties['sas_territory_ids'] = new ProcessorProperty([
        'label' => $this->t('SAS - Territory term IDs'),
        'description' => $this->t('Territory ids of the entity'),
        'type' => 'string',
        'is_list' => TRUE,
        'sanitized' => TRUE,
        'processor_id' => $this->getPluginId(),
      ]);

      // Territory labels.
      $properties['sas_territory_labels'] = new ProcessorProperty([
        'label' => $this->t('SAS - Territory term labels'),
        'description' => $this->t('Territory labels of the entity'),
        'type' => 'string',
        'is_list' => TRUE,
        'sanitized' => TRUE,
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

    $territory_tids = $this->territoryHelper->sasGetTerritoriesFromNode($entity);
    if (empty($territory_tids)) {
      return;
    }

    $fields = $item->getFields();
    $fields_sas_territory_ids = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'sas_territory_ids');
    $field_sas_territory_ids = reset($fields_sas_territory_ids);
    $fields_sas_territory_labels = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'sas_territory_labels');
    $fields_sas_territory_labels = reset($fields_sas_territory_labels);

    $territories = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($territory_tids);
    foreach ($territories as $territory) {
      $field_sas_territory_ids->addValue($territory->id());
      $fields_sas_territory_labels->addValue($territory->label());
    }
  }

}
