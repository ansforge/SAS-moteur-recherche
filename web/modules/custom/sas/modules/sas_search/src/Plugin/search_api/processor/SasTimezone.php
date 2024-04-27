<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\sas_geolocation\Service\SasTimezoneHelper;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sas timezone processor.
 *
 * @SearchApiProcessor(
 *   id = "sas_timezone",
 *   label = @Translation("field sas_timezone property"),
 *   description = @Translation("Adds the entity timezone to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasTimezone extends SasProcessorBase {

  /**
   * Timezone helper.
   *
   * @var \Drupal\sas_geolocation\Service\SasTimezoneHelper
   */
  protected ?SasTimezoneHelper $timezoneHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->timezoneHelper = $container->get('sas_geolocation.timezone');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $properties['sas_timezone'] = new ProcessorProperty([
        'label' => $this->t('SAS - Timezone'),
        'description' => $this->t('Field for entity timezone'),
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
    try {
      $entity = $item->getOriginalObject()->getEntity();
    }
    catch (SearchApiException $e) {
      return;
    }

    if (!$entity instanceof Node) {
      return;
    }

    if (!$this->snpContentHelper->isSupportSasSnpEntity($entity)) {
      return;
    }

    $fields = $item->getFields();
    $fields_sas_timezone = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_timezone');
    $fields_sas_timezone = reset($fields_sas_timezone);

    // Set sas_timezone solr property value.
    $sas_timezone = $this->timezoneHelper->getPlaceTimezone($entity);
    $fields_sas_timezone->addValue($sas_timezone);
  }

}
