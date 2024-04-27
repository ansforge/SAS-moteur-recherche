<?php

namespace Drupal\sas_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\user\UserAuth;

/**
 * SasUserAuth class.
 */
class SasUserAuth extends UserAuth {

  /**
   * Sas Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, PasswordInterface $password_checker, SasCoreServiceInterface $sasCoreService) {
    parent::__construct($entity_type_manager, $password_checker);
    $this->sasCoreService = $sasCoreService;
  }

  /**
   * {@inheritdoc}
   *
   * Allow authentication on SAS only for users with sas_* roles.
   */
  public function authenticate($username, $password) {
    $uid = parent::authenticate($username, $password);
    if ($uid && $this->sasCoreService->isSasContext()) {
      /** @var \Drupal\user\UserInterface $account_search */
      $account_search = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($account_search && !empty($account_search->getRoles(TRUE))) {
        $roles = $account_search->getRoles(TRUE);
        $roles = array_filter($roles, static fn ($value) => preg_match('/^sas_(.*)$/', $value) > 0);
        if (empty($roles)) {
          $uid = FALSE;
        }
      }
    }
    return $uid;
  }

}
