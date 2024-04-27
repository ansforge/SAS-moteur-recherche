<?php

namespace Drupal\sas_user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sante_user\Enum\SanteUserConstants;
use Drupal\sante_user\SanteUserAccessControlHandler;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SasUserAccessControlHandler custom user access handler.
 */
class SasUserAccessControlHandler extends SanteUserAccessControlHandler implements EntityHandlerInterface {

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static($entity_type);
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->requestStack = $container->get('request_stack');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);

    if ($this->sasCoreService->isSasContext()) {
      $account_admin_roles = array_intersect(SasUserConstants::SAS_ADMIN_USER_ROLES, $account->getRoles());
      $entity_admin_roles = array_intersect(SasUserConstants::SAS_ADMIN_USER_ROLES, $entity->getRoles());

      switch ($operation) {
        case 'update':
          $result = AccessResult::forbidden('Santé operation not allowed on SAS context')
            ->addCacheContexts([
              'url.site',
            ]);
          break;

        case 'sas_edit':
        case 'delete':
          if (!empty(array_intersect(SanteUserConstants::SANTE_ADMIN_ROLES, $account->getRoles(TRUE)))) {
            $result = AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
            break;
          }
          // Deny access for non-SAS-admins users on Santé user.
          if (!$entity->hasField('field_sas_user_sas')
            || $entity->get('field_sas_user_sas')->value != 1) {
            $result = AccessResult::forbidden('SAS operation not allowed on non-SAS user.')
              ->addCacheContexts(['url.site'])
              ->cachePerUser()
              ->addCacheableDependency($entity);
            break;
          }
          // Deny access for users with higher roles.
          if (!empty($entity_admin_roles) && array_key_first($entity_admin_roles) <= array_key_first($account_admin_roles)) {
            $result = AccessResult::forbidden('SAS Edit operation not allowed on user with higher role.')
              ->addCacheContexts(['url.site'])
              ->cachePerUser()
              ->addCacheableDependency($entity);
            break;
          }

          break;

        case 'view':
          $result = AccessResult::forbidden('View operation not allowed on SAS.')
            ->addCacheContexts(['url.site']);
          break;

        case 'sas_resend_email':
          if (!$entity->hasField('field_sas_user_sas')
            || $entity->get('field_sas_user_sas')->value != 1) {
            $result = AccessResult::forbidden('SAS operation not allowed on non-SAS user')
              ->addCacheContexts(['url.site'])
              ->addCacheableDependency($entity);
          }

          if (!$account->hasPermission('resend sas welcome email')) {
            $result = AccessResult::forbidden('Resend SAS Welcome mail operation not allowed for the current user.')
              ->cachePerUser()
              ->addCacheableDependency($entity);
          }
          break;

        case 'role_delegation':
          $result = AccessResult::forbidden('Santé operation not allowed on SAS')
            ->addCacheContexts(['url.site']);
          break;
      }

    }
    else {
      if (!empty(preg_match('/^sas_(.*)$/', $operation))) {
        $result = AccessResult::forbidden('SAS operation not allowed on Santé')
          ->addCacheContexts(['url.site']);
      }
      else {
        $sante_roles = array_diff($entity->getRoles(TRUE), SasUserConstants::getSasRoles());

        switch ($operation) {
          case 'update':
          case 'delete':
          case 'role_delegation':
            if (!empty(array_intersect(SanteUserConstants::SANTE_ADMIN_ROLES, $account->getRoles(TRUE)))) {
              $result = AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
            }
            elseif ($entity->hasField('field_sas_user_sas')
              && $entity->get('field_sas_user_sas')->value == 1
              && empty($sante_roles)) {
              $result = AccessResult::forbidden('Santé operation not allowed on SAS user')
                ->addCacheContexts(['url.site'])
                ->addCacheableDependency($entity);
            }
            break;
        }
      }
    }

    return $result;
  }

}
