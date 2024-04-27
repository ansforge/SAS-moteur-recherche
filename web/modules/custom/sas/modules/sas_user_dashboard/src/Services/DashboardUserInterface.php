<?php

namespace Drupal\sas_user_dashboard\Services;

use Drupal\user\UserInterface;

/**
 * The interface SasDashboardUser.
 */
interface DashboardUserInterface {

  /**
   * Optimized get delegation set for a given account.
   *
   * @param string $account
   *   User ID.
   *
   * @return array
   *   List of user account related to the given account.
   */
  public function sasUserGetDelegationsDashboardOptimized(string $account): array;

  /**
   * List the addresses linked to the user.
   *
   * @param array $fields
   *   Field structure and professional list.
   * @param \Drupal\user\UserInterface $user
   *   User ID.
   *
   * @return array
   *   list the addresses linked to the user.
   */
  public function sasDashboardUser(array $fields, UserInterface $user): array;

  /**
   * Get list of SOS Medecin association with their PFG.
   *
   * @param string[] $siret_list
   *   List of association siret.
   *
   * @return array
   *   List of SOS Medecin association with their list of PFG data.
   */
  public function getSosMedecinAssociations(array $siret_list, UserInterface $user): array;

  /**
   * Result to the entity sas_snp_user_data.
   *
   * @param string $user_id
   *   User ID.
   *
   * @return mixed
   *   result to the entity sas_snp_user_data.
   */
  public function sasGetEntitySasSnpUserData(string $user_id);

}
