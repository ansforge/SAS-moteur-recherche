<?php

namespace Drupal\sas_user\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Interface SasUserHelperInterface.
 *
 * Provides helpers to get user data relatives to SAS.
 */
interface SasUserHelperInterface {

  /**
   * Get the given content owner for SAS.
   *
   * Sas owner could be a Drupal user identified by uid
   * or a ProSante Connect identified by RPPS/ADELI.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to get owner.
   *
   * @return array
   *   Owner as array data.
   *   Containing :
   *    - user_id : user ID like uid or RPPS/ADELI
   *    - user_type : User type (Drupal or PSC)
   */
  public function getSasOwner(NodeInterface $node): array;

  /**
   * Get user account by its national ID.
   *
   * @param string $nationalId
   *   The practitioner national ID.
   *
   * @return object|bool
   *   Returns a user account or FALSE of not exists.
   */
  public function getAccountByCpx(string $nationalId): object|bool;

  /**
   * Retrait des user SAS - Délégataire.
   *
   * Par type : professionnel_de_sante ou par une
   * structure SAS_STRUCTURE_CONTENT_TYPE.
   *
   * @param string $uids
   *   The user id.
   *
   * @return array
   *   uid :id sas user rôle SAS - DELEGATAIRE.
   */
  public function getSasUserRelatedDelegataire(string $uids): array;

  /**
   * Retrieve account administrator email related to a territory.
   *
   * @param string $city
   *   The city name.
   *
   * @return array
   *   Empty array or array of email.
   */
  public function retrieveAccountAdministrators(string $city): array;

  /**
   * Check if sas delegataire exist.
   *
   * @param $email
   *   The delegataire email.
   *
   * @return bool
   */
  public function sasDelegataireExist($email): bool;

  /**
   * Get region ISO code form user account data.
   *
   * @param \Drupal\user\UserInterface $user
   *   User account.
   *
   * @return string|null
   *   Full name of user if data found or empty string else.
   */
  public function getUserRegionIsoCode(UserInterface $user): ?string;

  /**
   * Get timezone for a given user based on account region.
   *
   * @param \Drupal\user\UserInterface $user
   *   User account.
   * @param bool $textual
   *   To get textual timezone (like Europe/Paris).
   *
   * @return string|null
   *   Timezone.
   */
  public function getUserRegionTimezone(UserInterface $user, bool $textual = FALSE): ?string;

  /**
   * Get postal code users.
   *
   * @param \Drupal\user\UserInterface $user
   *   User account.
   *
   * @return array
   *   Codes.
   */
  public function getUserPostalCode(UserInterface $user): array;

  /**
   * Check if specified account has at least one SAS role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Drupal account.
   *
   * @return bool
   *   Returns TRUE if account has at least one SAS role, FALSE if not.
   */
  public function isSasUser(AccountInterface $account): bool;

  /**
   * Get user data based on the provided user ID. If no ID is provided,
   * or if the PSC user is valid, retrieves the current user.
   *
   * @param string $user_id
   *
   * @return mixed
   */
  public function getUserData(?string $user_id): mixed;

}
