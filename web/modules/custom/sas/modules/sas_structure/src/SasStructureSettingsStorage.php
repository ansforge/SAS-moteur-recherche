<?php

namespace Drupal\sas_structure;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\sas_structure\Entity\SasStructureSettings;

/**
 * Class SasStructureSettingsStorage.
 *
 * Provide specific storage for SasStructureSettings entity.
 *
 * @package Drupal\sas_structure
 */
class SasStructureSettingsStorage extends SqlContentEntityStorage implements SasStructureSettingsStorageInterface {

  /**
   * {@inheritDoc}
   */
  public function loadByStructureId(string $structure_id): ?SasStructureSettings {
    $result = $this->loadByProperties(['structure_id' => $structure_id]);

    return empty($result) ? NULL : reset($result);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(EntityInterface $entity) {
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $entity */

    $violations = $entity->validate();
    if ($violations->count() > 0) {
      throw new EntityStorageException($violations[0]->getMessage(), $violations[0]->getCode());
    }

    return parent::save($entity);
  }

}
