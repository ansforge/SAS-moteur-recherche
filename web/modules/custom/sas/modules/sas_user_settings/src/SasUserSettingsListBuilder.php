<?php

namespace Drupal\sas_user_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Sas user settings entities.
 *
 * @ingroup sas_user_settings
 */
class SasUserSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['id'] = $this->t('Sas user settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\sas_user_settings\Entity\SasUserSettings $entity */
    $row = [];
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.sas_user_settings.edit_form',
      ['sas_user_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
