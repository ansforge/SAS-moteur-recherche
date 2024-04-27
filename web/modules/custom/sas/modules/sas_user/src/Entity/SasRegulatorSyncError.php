<?php

namespace Drupal\sas_user\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SAS regulator synchronisation error entity.
 *
 * @ingroup sas_user
 *
 * @ContentEntityType(
 *   id = "sas_regulator_sync_error",
 *   label = @Translation("SAS regulator synchronisation error"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\sas_user\SasRegulatorSyncErrorAccessControlHandler",
 *   },
 *   base_table = "sas_regulator_sync_error",
 *   translatable = FALSE,
 *   admin_permission = "administer sas regulator synchronisation error entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SasRegulatorSyncError extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the SAS regulator synchronisation error entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE);

    $fields['payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Request payload'))
      ->setDescription(t('The payload send to aggregator.'))
      ->setDefaultValue('')
      ->setRequired(TRUE);

    $fields['error_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Response error code'))
      ->setDescription(t('Aggregator response error code.'))
      ->setSettings([
        'max_length' => 15,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setRequired(TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response error message'))
      ->setDescription(t('Aggregator response error message.'))
      ->setDefaultValue('')
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
