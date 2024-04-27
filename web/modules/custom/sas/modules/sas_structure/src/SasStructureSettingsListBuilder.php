<?php

namespace Drupal\sas_structure;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Sas structure settings entities.
 *
 * @ingroup sas_structure
 */
class SasStructureSettingsListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['id'] = $this->t('Sas structure settings ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $entity */
    $row = [];
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.sas_structure_settings.edit_form',
      ['sas_structure_settings' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
