<?php

namespace Drupal\sas_structure\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Sas structure settings entity.
 *
 * @ingroup sas_structure
 *
 * @ContentEntityType(
 *   id = "sas_structure_settings",
 *   label = @Translation("Sas structure settings"),
 *   handlers = {
 *     "storage" = "Drupal\sas_structure\SasStructureSettingsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sas_structure\SasStructureSettingsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\sas_structure\Form\SasStructureSettingsForm",
 *       "add" = "Drupal\sas_structure\Form\SasStructureSettingsForm",
 *       "edit" = "Drupal\sas_structure\Form\SasStructureSettingsForm",
 *       "delete" = "Drupal\sas_structure\Form\SasStructureSettingsDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\sas_structure\SasStructureSettingsHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\sas_structure\SasStructureSettingsAccessControlHandler",
 *   },
 *   base_table = "sas_structure_settings",
 *   translatable = FALSE,
 *   admin_permission = "administer sas structure settings entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/sas_structure_settings/{sas_structure_settings}",
 *     "add-form" = "/admin/structure/sas_structure_settings/add",
 *     "edit-form" = "/admin/structure/sas_structure_settings/{sas_structure_settings}/edit",
 *     "delete-form" = "/admin/structure/sas_structure_settings/{sas_structure_settings}/delete",
 *     "collection" = "/admin/structure/sas_structure_settings",
 *   },
 *   field_ui_base_route = "sas_structure_settings.settings",
 *   constraints = {
 *     "StructureSettingsValid" = {}
 *   }
 * )
 */
class SasStructureSettings extends ContentEntityBase {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['structure_id'] = BaseFieldDefinition::create('string')
      ->addConstraint('UniqueField')
      ->setLabel('Structure ID')
      ->setDescription('Structure ID like FINESS or SIRET')
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDisplayOptions('view', [
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setStorageRequired(TRUE)
      ->setRequired(TRUE);

    $fields['id_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Type'))
      ->setDescription(t("ID type (FINESS, SIRET)"))
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDisplayOptions('view', [
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setStorageRequired(TRUE)
      ->setRequired(TRUE);

    $fields['sas_participation'] = BaseFieldDefinition::create('boolean')
      ->setLabel('SAS Participation')
      ->setDescription('Is structure participating to SAS?')
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Participating to SAS')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
          'disabled' => TRUE,
        ],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 3,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['hours_available'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Availability declaration')
      ->setDescription('Is structure declaring to have at least tow hours available for SAS?')
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Declare to have at least tow hours available for SAS')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
          'disabled' => TRUE,
        ],
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 3,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['practitioner_count'] = BaseFieldDefinition::create('integer')
      ->setLabel('Practitioner count')
      ->setDescription('How many practitioner work for this structure.')
      ->setDefaultValue(0)
      ->setSettings([
        'unsigned' => TRUE,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['updated'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Last change author')
      ->setDescription('Store UID of last change author.')
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'project',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($this->get('id_type')->value == 'siret') {
      // Charger les nodes des points fixes de garde liés au siret.
      $siret = $this->get('structure_id')->value;
      $nodes_pfg = \Drupal::service('sas_structure.sos_medecin')->getAssociationPfg($siret, FALSE);

      // Indexer chaque node trouvé.
      foreach ($nodes_pfg as $id) {
        try {
          \Drupal::service('sas_search_index.helper')->indexSpecificItem($id);
        }
        catch (\Exception $e) {
          $this->getLogger('sas_structure.pfg-indexing')
            ->error('Error while indexing structure pfd: ' . $e->getMessage());
        }
      }
    }
    elseif ($this->get('id_type')->value === 'finess') {
      // Charger la structure liée au finess.
      $finess_number = $this->get('structure_id')->value;
      $node = \Drupal::service('sas_structure.finess_structure_helper')->getStructureByFiness($finess_number);

      // Si un node est trouvé, l'indexer.
      if ($node) {
        try {
          \Drupal::service('sas_search_index.helper')->indexSpecificItem($node->id());
        }
        catch (\Exception $e) {
          $this->getLogger('sas_structure.finess-indexing')
            ->error('Error while indexing finess structure: ' . $e->getMessage());
        }
      }
    }

  }

}
