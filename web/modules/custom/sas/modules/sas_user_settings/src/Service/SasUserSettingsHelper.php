<?php

namespace Drupal\sas_user_settings\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_structure\Service\StructureHelper;

/**
 * Class SasUserSettingsHelper.
 *
 * Provide helpers to get sas user settings.
 *
 * @package Drupal\sas_user_settings\Service
 */
class SasUserSettingsHelper implements SasUserSettingsHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * Structure helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelper
   */
  protected StructureHelper $structureHelper;

  /**
   * SasSnpUserDataHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $psc_user
   *   ProSanteConnect user helper.
   * @param \Drupal\sas_structure\Service\StructureHelper $structureHelper
   *   StructureHelper user helper.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SasKeycloakPscUserInterface $psc_user,
    StructureHelper $structureHelper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pscUser = $psc_user;
    $this->structureHelper = $structureHelper;
  }

  /**
   * Get if user have CGU in table sas_user_settings.
   *
   * @param int $user_id
   *   User ID (uid or ADELI/RPPS) used in Drupal.
   * @param int $type
   *   User type (drupal or psc).
   */
  public function updateUserCgu(int $user_id, int $type) {
    $cgu_exist = $this->getUserCgu($user_id);
    if (!$cgu_exist) {
      $timestamp = time();
      $user_settings = $this->entityTypeManager->getStorage('sas_user_settings')
        ->loadByProperties([
          'user_id' => $user_id,
          'user_type' => $type,
        ]);
      $user_settings = empty($user_settings) ? $this->entityTypeManager->getStorage('sas_user_settings')
        ->create([
          'user_id' => $user_id,
          'user_type' => $type,
        ]) : reset($user_settings);
      $user_settings->set('date_accept_cgu', $timestamp);
      $user_settings->set('cgu_accepted', TRUE);
      $user_settings->save();
    }
  }

  /**
   * Get if user have CGU in table sas_user_settings.
   *
   * @param int $user_id
   *   User ID (uid or ADELI/RPPS) used in Drupal.
   *
   * @return string
   *   User CGU.
   */
  public function getUserCgu(int $user_id): string {
    $user_settings = $this->entityTypeManager->getStorage('sas_user_settings')
      ->loadByProperties([
        'user_id' => $user_id,
      ]);
    if (empty($user_settings)) {
      return '';
    }
    /** @var \Drupal\sas_user_settings\Entity\SasUserSettings $user_settings */
    $user_settings = reset($user_settings);
    return $user_settings->get('cgu_accepted')->first()->getValue()['value'];
  }

}
