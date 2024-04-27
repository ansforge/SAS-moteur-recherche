<?php

namespace Drupal\sas_user_settings\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Sas user settings entity.
 *
 * @ingroup sas_user_settings
 *
 * @ContentEntityType(
 *   id = "sas_user_settings",
 *   label = @Translation("Sas user settings"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sas_user_settings\SasUserSettingsListBuilder",
 *     "views_data" = "Drupal\sas_user_settings\Entity\SasUserSettingsViewsData",
 *
 *     "access" = "Drupal\sas_user_settings\SasUserSettingsAccessControlHandler",
 *   },
 *   base_table = "sas_user_settings",
 *   translatable = FALSE,
 *   admin_permission = "administer sas user settings entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "user_id" = "user_id",
 *     "label" = "user_id",
 *     "user_type" = "user_type",
 *     "cgu_accepted" = "cgu_accepted",
 *     "date_accept_cgu" = "date_accept_cgu",
 *   },
 * )
 */
class SasUserSettings extends ContentEntityBase implements SasUserSettingsInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('user id'))
      ->setDescription(t('The user ID of the sas_user_settings entity.'))
      ->setSettings([
        'unsigned' => TRUE,
        'size' => 'big',
      ]);

    $fields['user_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('User type'))
      ->setDescription(t('The User type of the sas_snp_user_data entity.'))
      ->setSettings([
        'allowed_values' => [
          '1' => 'drupal users',
          '2' => 'psc users',
        ],
      ]);

    $fields['cgu_accepted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cgu accepted'))
      ->setDescription(t('A Cgu accepted of the sas_snp_user_data entity.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Cgu accepted');

    $fields['date_accept_cgu'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date accept cgu'))
      ->setDescription(t('The Date accept cgu of the sas_user_settings entity.'));

    return $fields;
  }

}
