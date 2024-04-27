<?php

namespace Drupal\sas_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sas_keycloak\Service\SasKeycloakPscUser;
use Drupal\sas_user\Service\SasUserHelperInterface;

/**
 * Class IsSasUserAccessCheck checks that we have a Drupal or PSC session.
 *
 * @package Drupal\sas_user\Access
 */
class IsSasUserAccessCheck implements AccessInterface {

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $sasUserHelper;

  /**
   * ProSanteConnect user.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUser
   */
  protected SasKeycloakPscUser $pscUser;

  /**
   * Constructs a SasSnpUserDataAddFormAccess object.
   *
   * @param \Drupal\sas_user\Service\SasUserHelperInterface $sasUserHelper
   *   SAS user helper.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUser $pscUser
   *   ProSanteConnect user helper.
   */
  public function __construct(
    SasUserHelperInterface $sasUserHelper,
    SasKeycloakPscUser $pscUser
  ) {
    $this->sasUserHelper = $sasUserHelper;
    $this->pscUser = $pscUser;
  }

  /**
   * @return string
   */
  public function appliesTo(): string {
    return '_is_sas_user_access_check';
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current account.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed
   *   Give access or not.
   */
  public function access(AccountInterface $account): AccessResult|AccessResultAllowed {
    if (
      ($account->isAnonymous() && $this->pscUser->isValid()) ||
      ($account->isAuthenticated() && $this->sasUserHelper->isSasUser($account))
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
