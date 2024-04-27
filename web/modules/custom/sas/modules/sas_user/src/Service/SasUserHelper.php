<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\NodeInterface;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_keycloak\Service\SasKeycloakPscUser;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\user\UserInterface;

/**
 * Class SasUserHelper.
 *
 * Implement function to provide data on Sas Users.
 *
 * @package Drupal\sas_user\Service
 */
class SasUserHelper implements SasUserHelperInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Sas Territory service.
   *
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected SasGetTermCodeCitiesInterface $sasTerritories;

  /**
   * Pro Santé Connect user service.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUser
   */
  protected SasKeycloakPscUser $pscUser;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * Current drupal account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * SasUserHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface $sasTerritories
   *   SAS term code cities service.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUser $pscUser
   *   Pro Santé Connect user service.
   * @param \Drupal\sas_user\Service\SasEffectorHelperInterface $sasEffectorHelper
   *   Sas user helper.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    SasGetTermCodeCitiesInterface $sasTerritories,
    SasKeycloakPscUser $pscUser,
    SasEffectorHelperInterface $sasEffectorHelper,
    AccountProxy $currentUser
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->sasTerritories = $sasTerritories;
    $this->pscUser = $pscUser;
    $this->sasEffectorHelper = $sasEffectorHelper;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritDoc}
   */
  public function getSasOwner(NodeInterface $node): array {
    $account_field = match ($node->bundle()) {
      'professionnel_de_sante' => 'field_sas_fiche_professionnel',
      'health_institution', 'finess_institution', 'service_de_sante' => 'field_sas_attach_structures',
      default => ''
    };

    if (empty($account_field)) {
      return [];
    }

    $result = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck()
      ->condition('status', 1)
      ->condition($account_field, $node->id())
      ->execute();

    if (!empty($result)) {
      // Ensure to have a valid integer id.
      $id = intval(reset($result));
      if (!empty($id)) {
        return [
          'user_id' => $id,
          'user_type' => SnpUserDataConstant::SAS_USER_TYPE_DRUPAL,

        ];
      }
    }

    $id_fields = [
      'field_identifiant_rpps',
      'field_personne_adeli_num',
    ];

    foreach ($id_fields as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        // Ensure to get a valid integer id.
        $id = intval($node->get($field_name)->value);
        if (!empty($id)) {
          return [
            'user_id' => $id,
            'user_type' => SnpUserDataConstant::SAS_USER_TYPE_PSC,
          ];
        }
      }
    }

    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getAccountByCpx(string $nationalId): object|bool {
    try {
      $users = $this->entityTypeManager
        ->getStorage('user')
        ->loadByProperties(['field_sas_numero_cpx' => $nationalId]);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    return $users ? reset($users) : FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getSasUserRelatedDelegataire(string $uids): array {
    if (empty($uids)) {
      return [];
    }

    $query = $this->database->select('users_field_data', 'u');
    $query->distinct();
    $query->fields('u', ['uid']);
    $query->addJoin('left', 'user__field_sas_related_pro', 'rp', 'u.uid = rp.entity_id');
    $query->addJoin('left', 'user__field_sas_rel_structure_manager', 'rsm', 'u.uid = rsm.entity_id');
    $query->join('user__roles', 'ur', 'ur.entity_id = u.uid');
    $query->condition('ur.roles_target_id', SnpConstant::SAS_DELEGATAIRE);
    $query->condition('u.status', 1);
    $orGroup = $query->orConditionGroup();
    $orGroup->condition('rp.field_sas_related_pro_target_id', $uids, 'IN');
    $orGroup->condition('rsm.field_sas_rel_structure_manager_target_id', $uids, 'IN');
    $query->condition($orGroup);
    return $query->execute()->fetchCol();
  }

  /**
   * {@inheritDoc}
   */
  public function retrieveAccountAdministrators(string $city): array {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->load($city);
    $accountAdmin = [];

    if (!empty($term)) {
      $postal_code = $term->get('field_postal_code')->value;
      $territoire_ids = $this->sasTerritories->sasGetTerritoriesFromPostalCode($postal_code);

      foreach ($territoire_ids as $id) {
        $results = $this->entityTypeManager->getStorage('user')->loadByProperties(
          [
            'roles' => 'sas_gestionnaire_de_comptes',
            'field_sas_territoire' => $id,
          ]
        );

        if ($results) {
          foreach ($results as $result) {
            $accountAdmin[] = $result->get('mail')->value;
          }
        }
      }
    }

    return $accountAdmin;
  }

  /**
   * {@inheritDoc}
   */
  public function sasDelegataireExist($email): bool {
    $query = $this->database->select('users_field_data', 'u');
    $query->fields('u', ['mail']);
    $query->condition('u.mail', $email, '=');
    $result = $query->execute()->fetchField();

    if (empty($result)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserRegionIsoCode(UserInterface $user): ?string {
    $region_iso = &drupal_static(__FUNCTION__);
    if (!isset($region_iso)) {
      if (!$user->hasField('field_region') || $user->get('field_region')->isEmpty()) {
        return NULL;
      }

      $regions = $user->get('field_region')->referencedEntities();
      /** @var \Drupal\taxonomy\TermInterface $region */
      $region = reset($regions);
      if (!$region->hasField('field_iso_code') || $region->get('field_iso_code')->isEmpty()) {
        return NULL;
      }

      $region_iso = $region->get('field_iso_code')->value;
    }
    return $region_iso;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserRegionTimezone(UserInterface $user, bool $textual = FALSE): ?string {
    $region_timezone = &drupal_static(__FUNCTION__);

    if (!isset($region_timezone)) {
      $region_iso = $this->getUserRegionIsoCode($user);
      if (empty($region_iso)) {
        return NULL;
      }

      if ($textual) {
        if (empty(SasUserConstants::TIMEZONE_REGION_MAPPING_TEXT[$region_iso])) {
          return NULL;
        }

        $region_timezone = SasUserConstants::TIMEZONE_REGION_MAPPING_TEXT[$region_iso];
      }
      else {
        if (empty(SasUserConstants::TIMEZONE_REGION_MAPPING[$region_iso])) {
          return NULL;
        }

        $region_timezone = SasUserConstants::TIMEZONE_REGION_MAPPING[$region_iso];
      }
    }

    return $region_timezone;
  }

  /**
   * {@inheritDoc}
   */
  public function getUserPostalCode(UserInterface $user): array {
    $cp = [];
    $fiche_pros = $attach_struc = [];

    $user->getRoles();

    $sheets = [
      'structure' => 'field_sas_rel_structure_manager',
      'professionals' => 'field_sas_related_pro',
    ];

    if (!$user->get('field_sas_fiche_professionnel')
      ->isEmpty()) {
      $fiche_pros = $user->get('field_sas_fiche_professionnel')
        ->referencedEntities();
    }

    if (!$user->get('field_sas_attach_structures')->isEmpty()) {
      $attach_struc = $user->get('field_sas_attach_structures')
        ->referencedEntities();
    }

    /** @var \Drupal\node\NodeInterface[] $fiches */
    $fiches = array_merge($fiche_pros, $attach_struc);

    foreach ($fiches as $fiche) {
      if (!$fiche->get('field_address')->isEmpty()) {
        $cp[] = $fiche->get('field_address')
          ->first()
          ->getValue()['postal_code'];
      }
    }

    $cp = array_unique($cp);

    foreach ($sheets as $sheet) {

      if (!$user->get($sheet)->isEmpty()) {
        $managers = $user->get($sheet)
          ->referencedEntities();

        if (!empty($managers)) {
          foreach ($managers as $manager) {

            if (!$manager->get('field_sas_codes_postaux')->isEmpty()) {
              $cp = array_unique(array_merge($cp, explode(',', $manager->get('field_sas_codes_postaux')->value)));
            }
          }
        }
      }
    }

    return $cp;
  }

  /**
   * {@inheritDoc}
   */
  public function isSasUser(AccountInterface $account): bool {
    $roles = $account->getRoles(TRUE);
    $roles = array_filter($roles, static fn ($value) => preg_match('/^sas_(.*)$/', $value) > 0);
    return !empty($roles);
  }

  /**
   * {@inheritDoc}
   */
  public function getUserData(?string $user_id): mixed {
    if (!$this->pscUser->isValid() && !empty($user_id)) {
      try {
        /**
         * @var \Drupal\user\Entity\User $user
         */
        $user = $this->entityTypeManager
          ->getStorage('user')
          ->loadByProperties([
            'field_sas_rpps_adeli' => $user_id,
          ]);

        $user = current($user);
        if (empty($user)) {
          $user = $this->sasEffectorHelper->getProfessionalSheetByUserId($user_id);
        }
      }
      catch (\Exception $e) {
        return NULL;
      }
    }
    elseif (!$this->pscUser->isValid() && !empty($this->currentUser->id())) {
      try {

        /**
         * @var \Drupal\user\Entity\User $user
         */
        $user = $this->entityTypeManager
          ->getStorage('user')
          ->load($this->currentUser->id());
      }
      catch (\Exception $e) {
        return NULL;
      }
    }
    elseif ($this->pscUser->isValid()) {

      $user = $this->pscUser->getCurrentUser();
    }
    else {

      $user = NULL;
    }
    return $user;
  }

}
