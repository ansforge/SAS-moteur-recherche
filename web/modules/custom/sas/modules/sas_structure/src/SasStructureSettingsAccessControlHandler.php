<?php

namespace Drupal\sas_structure;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Sas structure settings entity.
 *
 * @see \Drupal\sas_structure\Entity\SasStructureSettings.
 */
class SasStructureSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $entity */

    switch ($operation) {

      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view sas structure settings entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sas structure settings entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sas structure settings entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sas structure settings entities');
  }

}
