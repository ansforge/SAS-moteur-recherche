<?php

namespace Drupal\sas_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\sas_user\Service\SasDelegataireHelperInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * SasUserDataAccess class.
 */
class SasUserDataAccess implements AccessInterface {

  /**
   * CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * Sas effector user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $effectorHelper;

  /**
   * SAS délégataire helper.
   *
   * @var \Drupal\sas_user\Service\SasDelegataireHelperInterface
   */
  protected SasDelegataireHelperInterface $delegataireHelper;

  /**
   * PSC user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper
   */
  protected SasSnpUserDataHelper $sasSnpUserDataHelper;

  /**
   * DashboardAccess constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   Current route match.
   * @param \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard
   *   SAS user dashboard service.
   * @param \Drupal\sas_user\Service\SasEffectorHelperInterface $effectorHelper
   *   SAS effector user helper.
   * @param \Drupal\sas_user\Service\SasDelegataireHelperInterface $delegataireHelper
   *   SAS délégataire user helper.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $pscUser
   *   SAS PSC user helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper $sasSnpUserDataHelper
   *   Sas SNP User Data helper (to manager effector settings).
   */
  public function __construct(
    CurrentRouteMatch $currentRouteMatch,
    DashboardUserInterface $dashboard,
    SasEffectorHelperInterface $effectorHelper,
    SasDelegataireHelperInterface $delegataireHelper,
    SasKeycloakPscUserInterface $pscUser,
    EntityTypeManagerInterface $entity_type_manager,
    SasSnpUserDataHelper $sasSnpUserDataHelper
  ) {
    $this->currentRouteMatch = $currentRouteMatch;
    $this->dashboard = $dashboard;
    $this->effectorHelper = $effectorHelper;
    $this->delegataireHelper = $delegataireHelper;
    $this->pscUser = $pscUser;
    $this->entityTypeManager = $entity_type_manager;
    $this->sasSnpUserDataHelper = $sasSnpUserDataHelper;
  }

  /**
   * Access check for SAS users dashboard.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Request $request): AccessResultInterface {
    if (
      $this->isAdmin($account) ||
      $this->isOwner($account, $request) ||
      $this->hasDelegation($account, $request) ||
      $this->isGestionnaireCpts($account, $request)
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Manage access for SAS admin users.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account proxy.
   *
   * @return bool
   *   Give access or not.
   */
  private function isAdmin(AccountInterface $account): bool {
    return in_array(SasUserConstants::SAS_ADMIN_ROLE, $account->getRoles());
  }

  /**
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return bool
   *   Give access or not.
   */
  private function isOwner(AccountInterface $account, Request $request): bool {
    $userId = $request->query->get('userId');
    $currentUserNationalId = $this->effectorHelper->getCurrentUserNationalId();
    $access = FALSE;

    /*
     * Access for Effector user.
     */
    if (
      in_array(SasUserConstants::SAS_EFFECTOR_ROLE, $account->getRoles()) &&
      (empty($userId) || $userId == $currentUserNationalId)
    ) {
      $access = TRUE;
    }

    /*
     * Access for PSC users.
     */
    if (
      $this->pscUser->isValid() &&
      (empty($userId) || $userId == $currentUserNationalId)
    ) {
      $access = TRUE;
    }

    /*
     * Access for Gestionnaire de Structure user.
     */
    if (
      in_array(SasUserConstants::SAS_STRUCT_MANAGER_ROLE, $account->getRoles()) &&
      (empty($userId) || $userId == $account->id())
    ) {
      $access = TRUE;
    }

    /*
     * Access for route :
     * - sas_user_dashboard.delegataire
     */
    if (
      $this->currentRouteMatch->getRouteName() === 'sas_user_dashboard.delegataire' &&
      in_array(SasUserConstants::SAS_DELEGATE_ROLE, $account->getRoles()) &&
      $account->id() === $this->currentRouteMatch->getParameter('user')
    ) {
      $access = TRUE;
    }

    /*
     * Access for route :
     * - sas_user_dashboard.gestionnaire_de_structure
     */
    if (
      $this->currentRouteMatch->getRouteName() === 'sas_user_dashboard.gestionnaire_de_structure' &&
      in_array(SasUserConstants::SAS_STRUCT_MANAGER_ROLE, $account->getRoles()) &&
      $account->id() === $this->currentRouteMatch->getParameter('user')
    ) {
      $access = TRUE;
    }

    return $access;
  }

  /**
   * Manage access for "Délégataire" users.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account proxy.
   *
   * @return bool
   *   Give access or not.
   */
  private function hasDelegation(AccountInterface $account, Request $request): bool {
    $access = FALSE;

    if (in_array(SasUserConstants::SAS_DELEGATE_ROLE, $account->getRoles())) {
      // For effector delegation.
      // National id can be passed in url argument or in query parameters.
      $nationalId = $request->get('idNat') ?? $request->query->get('userId') ?? $request->get('national_id');

      // If $nationalId is empty, try to retrieve it from the request content.
      if (empty($nationalId)) {
        $content = $request->getContent();
        $data = json_decode($content, TRUE);
        $nationalId = $data['national_id'] ?? NULL;
      }
      if (
        !empty($nationalId) &&
        !empty($delegated = $this->delegataireHelper->getEffectorDelegations($account->id())) &&
        in_array($nationalId, $delegated)
      ) {
        $access = TRUE;
      }

      // For structure manager delegation.
      if (
        !empty($delegated = $this->dashboard->sasUserGetDelegationsDashboardOptimized($account->id())) &&
        in_array($this->currentRouteMatch->getParameter('user'), $delegated)
      ) {
        $access = TRUE;
      }
    }

    return $access;
  }

  /**
   * Determine if the current structure manager can access the dashboard of a PS.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account trying to access the dashboard.
   *
   * @return bool
   *   TRUE if the user can access the dashboard, FALSE otherwise.
   */
  private function isGestionnaireCpts(AccountInterface $account, Request $request): bool {

    $ps_rpps = empty($request->get('idNat')) ? $request->query->get('userId') : $request->get('idNat');
    if (NULL === $ps_rpps) {
      return FALSE;
    }
    $id_parts = $this->effectorHelper->getEffectorIdParts($ps_rpps);

    /** @var \Drupal\user\UserInterface $cpts_manager */
    $cpts_manager = $this->entityTypeManager->getStorage('user')->load($account->id());

    // Get only effector settings if given rpps is participating to sas with CPTS.
    $effector_settings = $this->sasSnpUserDataHelper->getSettingsBy(
      filters: [
        'user_id' => $id_parts['id'],
        'participation_sas_via' => SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS,
      ],
      toArray: FALSE
    );

    if (empty($effector_settings) || empty($cpts_manager)) {
      return FALSE;
    }

    $cpts_finess = $effector_settings->get('structure_finess')->value;
    if (!$cpts_manager->get(StructureConstant::CPTS_USER_FIELD_NAME)->isEmpty()) {
      $cpts_manager_finess_list = array_column(
        array: $cpts_manager->get(StructureConstant::CPTS_USER_FIELD_NAME)->getValue(),
        column_key: 'value'
      );
    }

    return !empty($cpts_finess) && !empty($cpts_manager_finess_list) && in_array($cpts_finess, $cpts_manager_finess_list);
  }

}
