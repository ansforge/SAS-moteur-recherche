<?php

namespace Drupal\sas_search\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelperInterface;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Drupal\sas_structure\Service\StructureHelperInterface;
use Drupal\sas_structure\Service\StructureSettingsHelperInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's Entity participate sas to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sas_user_settings",
 *   label = @Translation("SAS - Indexation for specific SAS User settings."),
 *   description = @Translation("Adds SAS User settings to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SasUserSettings extends SasProcessorBase {

  use SasSnpHelperTrait;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface|null
   */
  protected ?SasEffectorHelperInterface $effectorHelper;

  /**
   * Sas snp user data helper.
   *
   * @var \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelperInterface|null
   */
  protected ?SasSnpUserDataHelperInterface $snpUserDataHelper;

  /**
   * @var \Drupal\sas_structure\Service\StructureHelperInterface|null
   */
  protected ?StructureHelperInterface $structureHelper;

  /**
   * @var \Drupal\sas_structure\Service\StructureSettingsHelperInterface|null
   */
  protected ?StructureSettingsHelperInterface $structureSettingsHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface|null
   */
  protected ?SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->effectorHelper = $container->get('sas_user.effector_helper');
    $processor->snpUserDataHelper = $container->get('sas_snp_user_data.helper');
    $processor->structureHelper = $container->get('sas_structure.helper');
    $processor->structureSettingsHelper = $container->get('sas_structure.settings_helper');
    $processor->sosMedecinHelper = $container->get('sas_structure.sos_medecin');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      // BEGIN-NOSCAN.
      $properties['sas_overbooking'] = new ProcessorProperty([
        'label' => $this->t('SAS - Accept Overbooking'),
        'description' => $this->t('Property solr to store if overbooking is allowed'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_forfait_reo'] = new ProcessorProperty([
        'label' => $this->t('SAS - "Forfait de réorientation"'),
        'description' => $this->t('Property solr to store if "Forfait de réorientation" is enabled.'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_editor_disabled'] = new ProcessorProperty([
        'label' => $this->t('SAS - Editor disabled'),
        'description' => $this->t('Property solr to store if editor are disabled.'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_cpts_finess'] = new ProcessorProperty([
        'label' => $this->t('SAS - CPTS FINESS'),
        'description' => $this->t('Property solr to store CPTS FINESS.'),
        'type' => 'string',
        'sanitized' => TRUE,
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_cpts_label'] = new ProcessorProperty([
        'label' => $this->t('SAS - CPTS label'),
        'description' => $this->t('Property solr to store CPTS label.'),
        'type' => 'string',
        'sanitized' => TRUE,
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_cpts_phone'] = new ProcessorProperty([
        'label' => $this->t('SAS - CPTS phone number'),
        'description' => $this->t('Property solr to store CPTS phone number.'),
        'type' => 'string',
        'sanitized' => TRUE,
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_participation'] = new ProcessorProperty([
        'label' => $this->t('SAS - Participation'),
        'description' => $this->t('Entity participate sas'),
        'type' => 'boolean',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);

      $properties['sas_participation_via'] = new ProcessorProperty([
        'label' => $this->t('SAS - Participation via'),
        'description' => $this->t('Entity participate sas via'),
        'type' => 'integer',
        'is_list' => FALSE,
        'processor_id' => $this->getPluginId(),
      ]);
      // END-NOSCAN.
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getEntity();

    // Only for node.
    if (!$entity instanceof Node || !$this->snpContentHelper->isSupportSasSnpEntity($entity)) {
      return;
    }

    // Effector values definition.
    if ($entity->bundle() === 'professionnel_de_sante') {
      $this->addEffectorValues($item, $entity);
    }

    // Structure values definition.
    if ($entity->bundle() !== 'professionnel_de_sante') {
      $this->addStructureValues($item, $entity);
    }

  }

  /**
   * Add effector values to solr item to index.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   Solr item to index.
   * @param \Drupal\node\NodeInterface $entity
   *   Entity corresponding to solr item.
   */
  protected function addEffectorValues(ItemInterface $item, NodeInterface $entity) {
    $fields = $item->getFields();

    $field_sas_overbooking = $this->getField($fields, 'sas_overbooking');
    $field_emergency_reo = $this->getField($fields, 'sas_forfait_reo');
    $field_editor_disabled = $this->getField($fields, 'sas_editor_disabled');
    $field_cpts_finess = $this->getField($fields, 'sas_cpts_finess');
    $field_cpts_label = $this->getField($fields, 'sas_cpts_label');
    $field_cpts_phone = $this->getField($fields, 'sas_cpts_phone');
    $field_participation = $this->getField($fields, 'sas_participation');
    $field_participation_via = $this->getField($fields, 'sas_participation_via');

    $overbooking_status = $emergency_reo_status = $editor_disable = $participation = FALSE;

    if ($entity instanceof ProfessionnelDeSanteSas) {
      $id_nat = $entity->getNationalId();
    }

    if (!empty($id_nat)) {
      $data = $this->snpUserDataHelper->getSettingsBy([
        'user_id' => $id_nat['id'],
      ]);

      // Index participation only for certain participation via.
      if (
        !empty($data['participation_sas'][0]['value']) &&
        !empty($data['participation_sas_via'][0]['value'])
      ) {

        $participation_via = $data['participation_sas_via'][0]['value'];
        if (!in_array($data['participation_sas_via'][0]['value'], SnpUserDataConstant::PARTICIPATION_VIA_NOT_INDEXED)) {
          $overbooking_status = TRUE;
        }

        // If participation via CPTS, get CPTS data.
        if ($data['participation_sas_via'][0]['value'] == SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS) {
          $cpts = $this->getCptsData($entity, $data);
        }

        $participation = (bool) $data['participation_sas'][0]['value'] ?? FALSE;
      }

      // Forfait REO urgences.
      if (isset($data['forfait_reo_enabled'])) {
        $emergency_reo_status = $data['forfait_reo_enabled'][0]['value'];
        $field_emergency_reo->addValue($emergency_reo_status);
      }

      $finess = $entity->hasField('field_identifiant_str_finess') &&
      !empty($entity->get('field_identifiant_str_finess')->getValue()[0]['value'])
        ? $entity->get('field_identifiant_str_finess')->getValue()[0]['value']
        : NULL;
      if (!empty($finess)) {
        $node = $this->getContentByFiness($finess);

        if ($node && \Drupal::service('sas_structure.helper')->isCds($node)) {

          $overbooking_status = FALSE;
          $participation = FALSE;

        }
      }

      // Editor slots disabled.
      $editor_disable = isset($data) && !empty($data['editor_disabled'][0]['value']);

      // Set values to solr document fields.
      $field_sas_overbooking->addValue($overbooking_status);
      $field_editor_disabled->addValue($editor_disable);
      $field_cpts_finess->addValue($cpts['finess'] ?? NULL);
      $field_cpts_label->addValue($cpts['label'] ?? NULL);
      $field_cpts_phone->addValue($cpts['phone'] ?? NULL);
      $field_participation->addValue($participation);
      $field_participation_via->addValue($participation_via ?? 0);
    }

  }

  /**
   * Add structure values to solr item to index.
   *
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   Solr item to index.
   * @param \Drupal\node\NodeInterface $entity
   *   Entity corresponding to solr item.
   */
  protected function addStructureValues(ItemInterface $item, NodeInterface $entity): void {
    $fields = $item->getFields();
    $field_sas_overbooking = $this->getField($fields, 'sas_overbooking');
    $field_participation = $this->getField($fields, 'sas_participation');
    $structure_settings = [];
    $overbooking_status = FALSE;

    // For "Classic" structure identified by finess.
    $finess = $entity->hasField('field_identifiant_finess') &&
    !empty($entity->get('field_identifiant_finess')->getValue()[0]['value'])
      ? $entity->get('field_identifiant_finess')->getValue()[0]['value']
      : NULL;

    if (!empty($finess)) {
      if ($this->structureHelper->isMsp($entity)) {
        $structure_settings = $this->snpUserDataHelper->getSettingsBy([
          'participation_sas' => TRUE,
          'participation_sas_via' => SnpUserDataConstant::SAS_PARTICIPATION_MY_MSP,
          'structure_finess' => $finess,
        ]);

        $overbooking_status = !empty($structure_settings['participation_sas'][0]['value']);
      }

      if ($this->structureHelper->isCds($entity)) {
        $structure_settings = $this->structureSettingsHelper->getSettingsBy([
          'sas_participation' => TRUE,
          'structure_id' => $finess,
        ]);

        $overbooking_status = !empty($structure_settings['sas_participation'][0]['value']);
      }
    }

    // For "SOS Médecin" entities (Point Fixe de Garde).
    $siret = $entity->hasField('field_identif_siret') && !$entity->get('field_identif_siret')->isEmpty()
      ? $entity->get('field_identif_siret')->value
      : NULL;
    if (!empty($siret) && $this->sosMedecinHelper->isSosMedecinAssociation($siret)) {
      $structure_settings = $this->structureSettingsHelper->getSettingsBy([
        'sas_participation' => TRUE,
        'structure_id' => $siret,
      ]);

      $overbooking_status = !empty($structure_settings['sas_participation'][0]['value']);
    }

    // Today for structure, participation to sas is always the same as overbooking.
    $participation = $overbooking_status;

    $field_sas_overbooking->addValue($overbooking_status);
    $field_participation->addValue($participation);
  }

  /**
   * Extract field form document fields.
   *
   * @param array $fields
   *   List of documents fields.
   * @param $name
   *   Name of wanted field.
   *
   * @return \Drupal\search_api\Item\FieldInterface|false
   *   Document field.
   */
  protected function getField(array $fields, $name): bool|FieldInterface {
    $found_field = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, $name);
    return reset($found_field);
  }

  /**
   * Get CPTS Data to index.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to index.
   * @param array $data
   *   Settings data associated to entity to index.
   *
   * @return array
   *   CPTS data found containing :
   *    - finess
   *    - label
   *    - phone
   */
  protected function getCptsData(EntityInterface $entity, array $data): array {

    $cpts = [];
    $finess = $data['structure_finess'][0]['value'] ?? NULL;

    if (
      !empty($finess) &&
      $entity->hasField('field_identifiant_active_rpps') &&
      !$entity->get('field_identifiant_active_rpps')->isEmpty()
    ) {
      $rpps = $entity->get('field_identifiant_active_rpps')->getValue()[0]['value'];

      if (!empty($data['cpts_locations'][0]['value']) && in_array($rpps, $data['cpts_locations'][0]['value'])) {
        $cpts['finess'] = $finess;
        $structure_data = $this->structureHelper->getStructureDataByFiness($finess);

        if (!empty($structure_data)) {
          $cpts['label'] = $structure_data->title ?? NULL;
          $cpts['phone'] = $structure_data->field_telephone_fixe_value ?? NULL;
        }
      }
    }

    return $cpts;
  }

}
