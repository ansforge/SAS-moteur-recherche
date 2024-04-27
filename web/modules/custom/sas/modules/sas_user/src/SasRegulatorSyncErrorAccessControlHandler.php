<?php

namespace Drupal\sas_user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the SAS regulator synchronisation error entity.
 *
 * @see \Drupal\sas_user\Entity\SasRegulatorSyncError.
 *
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class SasRegulatorSyncErrorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\sas_user\Entity\SasRegulatorSyncError $entity */

    // Unknown operation, no opinion.
    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account,
        'view published sas regulator synchronisation error entities'),
      'update' => AccessResult::allowedIfHasPermission($account,
        'edit sas regulator synchronisation error entities'),
      'delete' => AccessResult::allowedIfHasPermission($account,
        'delete sas regulator synchronisation error entities'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sas regulator synchronisation error entities');
  }

}
