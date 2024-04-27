<?php

namespace Drupal\sas_entity_snp_user\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_user\Enum\SasUserConstants;

/**
 * Defines the sas snp user data entity class.
 *
 * @ContentEntityType(
 *   id = "sas_snp_user_data",
 *   label = @Translation("SAS effectors settings"),
 *   base_table = "sas_snp_user_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "editor_disabled" = "editor_disabled",
 *     "forfait_reo_enabled" = "forfait_reo_enabled",
 *     "participation_sas" = "participation_sas",
 *     "participation_sas_via" = "participation_sas_via",
 *     "structure_finess" = "structure_finess",
 *     "has_software" = "has_software",
 *     "hours_available" = "hours_available",
 *     "settings_updated" = "settings_updated",
 *     "cpts_locations" = "cpts_locations",
 *     "siret" = "siret"
 *   },
 *   constraints = {
 *     "EffectorSettingsValid" = {}
 *   }
 * )
 */
class SasSnpUserData extends ContentEntityBase implements SasSnpUserDataInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('integer')
      ->setDescription(t('Effector RPPS or ADELI.'))
      ->setSettings([
        'unsigned' => TRUE,
        'size' => 'big',
      ])
      ->addConstraint('UniqueField');

    $fields['editor_disabled'] = BaseFieldDefinition::create('boolean')
      ->setDescription(t('Editor disabled of the sas_snp_user_data entity.'))
      ->setDefaultValue(FALSE);

    $fields['forfait_reo_enabled'] = BaseFieldDefinition::create('boolean')
      ->setDescription(t('A Forfait reo enabled of the sas_snp_user_data entity.'))
      ->setDefaultValue(FALSE);

    $fields['participation_sas'] = BaseFieldDefinition::create('boolean')
      ->setDescription(t("A Participation sas of the sas_snp_user_data entity."))
      ->setDefaultValue(FALSE);

    $fields['participation_sas_via'] = BaseFieldDefinition::create('integer')
      ->setDefaultValue(NULL);

    $fields['has_software'] = BaseFieldDefinition::create('boolean')
      ->setDescription(t("Store if user has an appointment software."))
      ->setDefaultValue(NULL);

    $fields['hours_available'] = BaseFieldDefinition::create('boolean')
      ->setDescription(t("Store if user declare to have at least two hours available for SAS."))
      ->setDefaultValue(NULL);

    $fields['structure_finess'] = BaseFieldDefinition::create('string')
      ->setDescription(t("Store user structure FINESS"))
      ->setDefaultValue(NULL)
      ->setSettings([
        'max_length' => 50,
      ]);

    $fields['siret'] = BaseFieldDefinition::create('string')
      ->setDescription(t("Store siret of structure."))
      ->setDefaultValue(NULL)
      ->setSettings([
        'max_length' => 50,
      ]);

    $fields['cpts_locations'] = BaseFieldDefinition::create('string_long')
      ->setDescription(t('Store CPTS locations'))
      ->setDefaultValue(NULL)
      ->setSetting('case_sensitive', TRUE);

    $fields['settings_updated'] = BaseFieldDefinition::create('changed')
      ->setDescription(t("Store last settings update date"))
      ->setDefaultValue(time())
      ->setRequired(TRUE);

    $fields['user_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('User type'))
      ->setDescription(t('The User type of the sas_snp_user_data entity.'))
      ->setSettings([
        'allowed_values' => [
          '1' => 'drupal users',
          '2' => 'psc users',
        ],
      ])
      ->setDefaultValue('2');

    $fields['cgu_accepted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cgu accepted'))
      ->setDescription(t('A Cgu accepted of the sas_snp_user_data entity.'))
      ->setDefaultValue(FALSE);

    $fields['date_accept_cgu'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date accept cgu'))
      ->setDescription(t('The Date accept cgu of the sas_snp_user_data entity.'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $userPlaces = \Drupal::service('sas_user.effector_helper')
      ->getContentByRppsAdeli($this->user_id->value, SasUserConstants::PREFIX_ID_RPPS);

    if (empty($userPlaces)) {
      $userPlaces = \Drupal::service('sas_user.effector_helper')
        ->getContentByRppsAdeli($this->user_id->value, SasUserConstants::PREFIX_ID_ADELI);
    }

    $nodesToReindex = $this->entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($userPlaces);

    if (
      (
        $this->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_MSP ||
        $this->original->participation_sas_via->value == SnpUserDataConstant::SAS_PARTICIPATION_MY_MSP
      ) &&
      $this->structure_finess->value != $this->original->structure_finess->value
    ) {
      $structuresFiness = array_filter([
        $this->structure_finess->value,
        $this->original->structure_finess->value,
      ]);

      $structures = $this->entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'field_identifiant_finess' => $structuresFiness,
        ]);

      $nodesToReindex = array_merge($nodesToReindex, $structures);
    }

    /** @var \Drupal\node\Entity\Node $node */
    foreach ($nodesToReindex as $node) {
      if (
        $node->hasField('field_editor_slots_disabled') &&
        $node->field_editor_slots_disabled->value != $this->editor_disabled->value
      ) {
        $node->set('field_editor_slots_disabled', $this->editor_disabled->value);

        try {
          $node->save();
        }
        catch (EntityStorageException $e) {
          continue;
        }
      }

      \Drupal::service('sas_search_index.helper')->indexSpecificItem($node->id());
    }
  }

}
