<?php

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\ProfessionnelDeSante;
use Drupal\sas_directory_pages\Entity\Feature\AggregatorLinkInterface;
use Drupal\sas_directory_pages\Entity\Feature\AggregLinkTrait;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\user\Entity\User;

/**
 * ProfessionnelDeSanteSas class.
 */
class ProfessionnelDeSanteSas extends ProfessionnelDeSante implements AggregatorLinkInterface, SasSnpHelperInterface {

  use AggregLinkTrait;
  use SasSnpHelperTrait;

  /**
   * Get the PS' "effecteur" if available.
   *
   * @return \Drupal\user\Entity\User|null
   */
  public function getEffecteurAccount(): User|NULL {
    $user_storage = $this->entityTypeManager()->getStorage('user');
    $query = $user_storage
      ->getQuery()->accessCheck()
      ->condition('field_sas_fiche_professionnel', $this->id());
    $result = $query->execute();
    if (!empty($result) && is_array($result)) {
      return $user_storage->load(reset($result));
    }
    return NULL;
  }

  /**
   * Get data from PS' effecteur.
   */
  public function getEffecteurData($field_name) {
    // Get our PS' "effecteur".
    /** @var \Drupal\user\Entity\User $effecteur */
    $effecteur = $this->getEffecteurAccount();
    $this->addCacheableDependency($effecteur);

    $id_effecteur = $this->get('field_identifiant_rpps')
      ->value ?? $this->get('field_personne_adeli_num')->value;

    if (!$id_effecteur) {
      return NULL;
    }

    /** @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard_users_service */
    $dashboard_users_service = \Drupal::service('sas_user_dashboard.dashboard');
    /** @var \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $sas_snp_user_data */
    $sas_snp_user_data = $dashboard_users_service->sasGetEntitySasSnpUserData($id_effecteur);
    if (!$sas_snp_user_data) {
      return NULL;
    }
    $this->addCacheableDependency($sas_snp_user_data);
    return $sas_snp_user_data
      ->get($field_name)
      ->first()
      ->getValue()['value'];
  }

  /**
   * Extract effector national ID (RPPS/ADELI) from professional sheet.
   *
   * @return array|null
   *   National ID data as an array with :
   *    - prefix : Type of id, 8 for RPPS and 0 for ADELI.
   *    - id : National ID.
   */
  public function getNationalId(): ?array {
    if ($this->hasField('field_identifiant_rpps') && !$this->get('field_identifiant_rpps')->isEmpty()) {
      return [
        'prefix' => SasUserConstants::PREFIX_ID_RPPS,
        'id' => $this->get('field_identifiant_rpps')->value,
      ];
    }

    if ($this->hasField('field_personne_adeli_num') && !$this->get('field_personne_adeli_num')->isEmpty()) {
      return [
        'prefix' => SasUserConstants::PREFIX_ID_ADELI,
        'id' => $this->get('field_personne_adeli_num')->value,
      ];
    }

    return NULL;
  }

  /**
   * Extract effector national ID (RPPS/ADELI) from professional sheet.
   *
   * @return string|null
   *   National ID data as a String with :
   */
  public function getNationalIdAsString(): ?string {
    $idNat = $this->getNationalId();
    if (empty($idNat)) {
      return NULL;
    }

    return sprintf("%s%s", $idNat['prefix'], $idNat["id"]);
  }

}
