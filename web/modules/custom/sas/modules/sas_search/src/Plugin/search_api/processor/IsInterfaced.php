<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\node\Entity\Node;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_snp\Service\InterfacedHelper;
use Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper;
use Drupal\sas_structure\Service\SosMedecinHelper;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's "isInterfaced" value to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "is_interfaced",
 *   label = @Translation("SAS - Is Interfaced"),
 *   description = @Translation("Indexes the isInterfaced property to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class IsInterfaced extends SasProcessorBase {

  /**
   * @var \Drupal\sas_snp\Service\InterfacedHelper
   */
  private InterfacedHelper $interfacedHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper
   */
  private SosDoctorsIsInterfacedHelper $sosDoctorsIsInterfacedHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelper
   */
  private SosMedecinHelper $sosMedecinHelper;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL): array {
    $properties = [];

    if (!$datasource) {
      $properties['sas_is_interfaced'] = new ProcessorProperty([
        'label' => $this->t('SAS - Is Interfaced'),
        'description' => $this->t('Is interfaced field from table sas_snp_availability'),
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
    $processor->interfacedHelper = $container->get('sas_snp.interfaced_helper');
    $processor->sosMedecinHelper = $container->get('sas_structure.sos_medecin');
    $processor->sosDoctorsIsInterfacedHelper = $container->get('sas_structure.sos_doctors_interfaced_helper');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get the NID of the current item (e.g., node).
    $entity = $item->getOriginalObject()->getEntity();

    // Only for node.
    if (!$entity instanceof Node) {
      return;
    }

    if (!$this->snpContentHelper->isSupportSasSnpEntity($entity)) {
      return;
    }

    $fields = $item->getFields();
    $fields_is_interfaced = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'sas_is_interfaced');
    $field_is_interfaced = reset($fields_is_interfaced);

    $is_interfaced = FALSE;

    if ($entity instanceof ProfessionnelDeSanteSas) {
      $id_nat = $entity->getNationalIdAsString();
      $is_interfaced = $this->interfacedHelper->isInterfaced($id_nat);
    }

    // Index is_interfaced pfg.
    $siret = $entity->hasField('field_identif_siret') &&
    !$entity->get('field_identif_siret')->isEmpty() ?
      $entity->get('field_identif_siret')->value : NULL;
    if (!empty($siret) && $this->sosMedecinHelper->isSosMedecinAssociation($siret)) {
      $is_interfaced = $this->sosDoctorsIsInterfacedHelper->isSosDoctorsInterfaced($siret);
    }

    $field_is_interfaced->addValue($is_interfaced);
  }

}
