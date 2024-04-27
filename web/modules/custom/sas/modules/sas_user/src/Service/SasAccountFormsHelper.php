<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\sas_keycloak\Service\SasKeycloakMail;
use Drupal\sas_keycloak\Service\SasKeycloakManager;
use Drupal\sas_keycloak\Service\SasKeycloakUserHelperInterface;
use Drupal\sas_keycloak\Service\SasKeycloakUserInfo;

/**
 * Provides account form helpers.
 */
class SasAccountFormsHelper implements SasAccountFormsHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUserHelperInterface
   */
  protected SasKeycloakUserHelperInterface $sasKeycloakUserHelper;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakManager
   */
  protected SasKeycloakManager $sasKeycloakManager;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUserInfo
   */
  protected SasKeycloakUserInfo $sasKeycloakUserInfo;

  /**
   * @var \Drupal\sas_keycloak\Service\SasKeycloakMail
   */
  protected SasKeycloakMail $sasKeycloakMail;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $current_user,
    SasKeycloakUserHelperInterface $sasKeycloakUserHelper,
    SasKeycloakManager $sasKeycloakManager,
    SasKeycloakUserInfo $sasKeycloakUserInfo,
    SasKeycloakMail $sasKeycloakMail
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->sasKeycloakUserHelper = $sasKeycloakUserHelper;
    $this->sasKeycloakManager = $sasKeycloakManager;
    $this->sasKeycloakUserInfo = $sasKeycloakUserInfo;
    $this->sasKeycloakMail = $sasKeycloakMail;
  }

  /**
   * {@inheritDoc}
   */
  public function getRenewButtonOverride(): ?array {

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    if ($this->sasKeycloakUserHelper->hasLoginPasswordAccess($account)) {
      return [
        '#type' => 'button',
        '#value' => 'Renouveler votre mot de passe',
        '#prefix' => '<div id="pwd_btn">',
        '#suffix' => '</div>',
        '#ajax' => [
          'callback' => '::passwordRenewSubmit',
        ],
      ];
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function makeRenewEmailSendAction(): void {
    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $is_sas_user = $current_user->get('field_sas_user_sas')->getString() ?? 0;

    // If not SAS user, create user in Keycloak.
    if ($is_sas_user && $this->sasKeycloakManager->isFeatureEnabled('synchro')) {
      $keycloak_uid = $this->sasKeycloakUserInfo->getKeycloakUid($current_user);
      if (!empty($keycloak_uid)) {
        $this->sasKeycloakMail->sendPasswordRenewEmail($keycloak_uid);
      }
    }
  }

}
