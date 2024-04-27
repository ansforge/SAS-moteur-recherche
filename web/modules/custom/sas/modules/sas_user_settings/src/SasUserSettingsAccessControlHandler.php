<?php

namespace Drupal\sas_user_settings;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Sas user settings entity.
 *
 * @see \Drupal\sas_user_settings\Entity\SasUserSettings.
 */
class SasUserSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\sas_user_settings\Entity\SasUserSettingsInterface $entity */

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished sas user settings entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published sas user settings entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sas user settings entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sas user settings entities');

      default:
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sas user settings entities');
  }

}
