<?php

namespace Drupal\sas_user\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\user\UserInterface;

/**
 * Class SasEffectorHelper.
 *
 * Specific helper for effector user account.
 *
 * @package Drupal\sas_user\Service
 */
class SasEffectorHelper implements SasEffectorHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * PSC user service.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * @var \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper
   */
  protected SasSnpUserDataHelper $sasSnpUserDataHelper;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $pscUser
   *   PSC user service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    AccountProxy $currentUser,
    SasKeycloakPscUserInterface $pscUser,
    SasSnpUserDataHelper $sasSnpUserDataHelper
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->currentUser = $currentUser;
    $this->pscUser = $pscUser;
    $this->sasSnpUserDataHelper = $sasSnpUserDataHelper;
  }

  /**
   * {@inheritDoc}
   */
  public function getAllEffectors(): ?array {
    return $this->entityTypeManager->getStorage('user')->loadByProperties([
      'roles' => SasUserConstants::SAS_EFFECTOR_ROLE,
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function getEffectorRppsAdeliBySheets(UserInterface $user): ?array {
    $rpps_adeli = [];

    if ($user->hasField('field_sas_fiche_professionnel') && !$user->get('field_sas_fiche_professionnel')->isEmpty()) {
      /** @var \Drupal\node\NodeInterface[] $sheets */
      $sheets = $user->get('field_sas_fiche_professionnel')->referencedEntities();

      foreach ($sheets as $sheet) {
        if ($sheet instanceof ProfessionnelDeSanteSas) {
          $id_nat = $sheet->getNationalId();
        }

        if (!empty($id_nat)) {
          $rpps_adeli[] = $id_nat;
        }
      }

      $rpps_adeli = array_unique($rpps_adeli, SORT_REGULAR);
    }

    return $rpps_adeli;
  }

  /**
   * {@inheritDoc}
   */
  public function getContentByRppsAdeli(string $rpps_adeli_num, $prefix): array {
    $nid = [];
    if ($prefix == SasUserConstants::PREFIX_ID_RPPS) {
      $query = $this->database->select('node__field_identifiant_rpps', 'i');
      $query->condition('i.field_identifiant_rpps_value', $rpps_adeli_num);
    }
    else {
      $query = $this->database->select('node__field_personne_adeli_num', 'i');
      $query->condition('i.field_personne_adeli_num_value', $rpps_adeli_num);
    }

    $query->leftJoin('node_field_data', 'n', 'i.entity_id = n.nid');
    $query->condition('n.status', 1);
    $query = $query->fields('i', ['entity_id']);
    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      return [];
    }

    foreach ($results as $result) {
      $nid[] = $result->entity_id;
    }
    if (!empty($nid)) {
      return $nid;
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getActivityRppsNids(array $activity_rpps_list): array {

    if (empty($activity_rpps_list)) {
      return [];
    }
    $query = $this->database->select('node__field_identifiant_active_rpps', 'nfar');
    $query->addField('nfar', 'entity_id');
    $query->condition('nfar.field_identifiant_active_rpps_value', $activity_rpps_list, 'IN');
    $result = $query->execute()->fetchCol();

    if (empty($result)) {
      return [];
    }

    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getAddresses(string $id_nat, bool $cpts_only = FALSE): array {

    $addresses = [];

    $id_parts = $this->getEffectorIdParts($id_nat);
    if (empty($id_parts)) {
      return [];
    }

    $ids = $this->getContentByRppsAdeli(
      rpps_adeli_num: $id_parts['id'],
      prefix: $id_parts['prefix']
    );

    if (!empty($ids)) {
      try {
        /** @var \Drupal\node\NodeInterface[] $sheets */
        $sheets = $this->entityTypeManager->getStorage('node')
          ->loadMultiple($ids);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        return [];
      }

      if (!empty($sheets)) {
        $addresses = $this->buildAddressList(
          id_nat: $id_parts['id'],
          sheets: $sheets,
          cpts_only: $cpts_only
        );
      }
    }

    return $addresses;
  }

  protected function buildAddressList(string $id_nat, array $sheets, bool $cpts_only = FALSE): array {
    $addresses = [];

    // Get only effector settings if given rpps is participating to sas with CPTS.
    $effector_settings = $this->sasSnpUserDataHelper->getSettingsBy(
      filters: [
        'user_id' => $id_nat,
        'participation_sas_via' => SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS,
      ],
      toArray: FALSE
    );

    // Extract places mapped to a cpts.
    if (!empty($effector_settings) && !$effector_settings->get('cpts_locations')->isEmpty()) {
      $cpts_activity_rrps_list = unserialize(
        data: $effector_settings->get('cpts_locations')->value,
        options: [
          'allowed_classes' => FALSE,
        ]
      );
    }

    foreach ($sheets as $sheet) {
      if (
        $cpts_only
        && !empty($cpts_activity_rrps_list)
      ) {
        $current_activity_rpps = $sheet->get('field_identifiant_active_rpps')->value;
        if (!empty($current_activity_rpps) && !in_array($current_activity_rpps, $cpts_activity_rrps_list)) {
          continue;
        }
      }

      if ($sheet instanceof ProfessionnelDeSanteSas) {
        $addresses[] = $sheet->getPlaceData();
      }
    }

    return $addresses;
  }

  /**
   * {@inheritDoc}
   */
  public function isExistingContentByRppsAdeli(string $rpps_adeli_num, $prefix): bool {
    $nids = $this->getContentByRppsAdeli($rpps_adeli_num, $prefix);

    return !empty($nids);
  }

  /**
   * {@inheritDoc}
   */
  public function userRppsAdeliExists(string $rpps_adeli_num): bool {
    $query = $this->entityTypeManager->getStorage('user')
      ->getQuery()->accessCheck();
    $orGroup = $query->orConditionGroup()
      ->condition('field_sas_rpps_adeli', '8' . $rpps_adeli_num)
      ->condition('field_sas_rpps_adeli', '0' . $rpps_adeli_num);
    $query->accessCheck(FALSE)
      ->condition($orGroup)
      ->count();
    $results = $query->execute();

    return !empty($results) && $results > 0;
  }

  /**
   * {@inheritDoc}
   */
  public function getEffectorIdParts(string $rpps_adeli_num): array {
    $matches = [];
    if (preg_match('/^(0|8)(\d+)$/', $rpps_adeli_num, $matches)) {
      return [
        'prefix' => $matches[1],
        'id' => $matches[2],
      ];
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getRppsAdeliInUserId(string $uid): string {
    $current_user = $this->entityTypeManager
      ->getStorage('user')->load($uid);
    if ($current_user && !empty($current_user->field_sas_rpps_adeli->value)) {
      $user_id = $this->getEffectorIdParts($current_user->field_sas_rpps_adeli->value)['id'];
    }
    return $user_id ?? '';
  }

  /**
   * {@inheritDoc}
   */
  public function isUserIdSettingsExists(string $rpps_adeli_num): bool {
    $query = $this->entityTypeManager->getStorage('sas_snp_user_data')
      ->getQuery()->accessCheck()
      ->condition('user_id', $rpps_adeli_num)
      ->count();
    $results = $query->execute();

    return !empty($results) && $results > 0;
  }

  /**
   * {@inheritDoc}
   */
  public function getCurrentUserNationalId(): ?int {
    if ($this->pscUser->isValid()) {
      return $this->pscUser->getPscIdNat();
    }

    try {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($this->currentUser->id());
    }
    catch (\Exception $e) {
      return NULL;
    }

    return $user->field_sas_rpps_adeli->value ?? NULL;
  }

  /**
   * Get the current user.
   *
   * @return Drupal\sas_user\Service\AccountProxy
   */
  public function getCurrentUser(): AccountProxy {
    return $this->currentUser;
  }

  /**
   * Loads a user's professional sheet based on the provided user ID.
   *
   * @param mixed $user_id
   *   The user ID.
   *
   * @return mixed
   *   Returns the corresponding node representing the PS sheet if found
   */
  public function getProfessionalSheetByUserId(mixed $user_id) {
    $id_parts = $this->getEffectorIdParts($user_id);
    // Retrieves the specific user ID from the extracted parts.
    $id_user = $id_parts['id'] ?? NULL;

    if (empty($id_user)) {
      return NULL;
    }

    // Retrieves node IDs associated with the user.
    $nids = $this->getContentByRppsAdeli($id_user, SasUserConstants::PREFIX_ID_RPPS);

    if (!empty($nids)) {
      $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
      return $node ?: NULL;
    }

    return NULL;
  }

}
