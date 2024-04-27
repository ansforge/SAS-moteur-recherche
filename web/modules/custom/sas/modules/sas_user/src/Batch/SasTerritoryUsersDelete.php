<?php

namespace Drupal\sas_user\Batch;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;

/**
 * Batch Update users in delete Territory.
 */
class SasTerritoryUsersDelete {

  /**
   * Get a batch operations Update users in delete Territory.
   *
   * @param $term
   *   Terms taxo.
   *
   * @return array
   *   The built batch object.
   */
  public static function getBatch(TermInterface $term): array {

    $operations = [];
    $operations[] = [
      [static::class, 'territoryUserDelete'],
      [$term],
    ];

    return [
      'title' => t('Mise à jours des utilisateurs'),
      'operations' => $operations,
      'finished' => [__CLASS__, 'deleteUserFinished'],
    ];

  }

  /**
   * Update users in delete Territory.
   *   Code postal in term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Term taxo.
   * @param array $context
   *   Batch context array.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function territoryUserDelete(TermInterface $term, array &$context) {
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['results']['count'] = $context['sandbox']['max'] = self::getUserUidsDeleteTerritory($term, TRUE);
      $context['sandbox']['users'] = self::getUserUidsDeleteTerritory($term);
    }

    $users = User::loadMultiple(array_slice($context['sandbox']['users'], $context['sandbox']['progress'], 50));

    foreach ($users as $user) {
      if (!$user->get('field_sas_territoire')->isEmpty()) {
        $field_values = $user->get('field_sas_territoire')->getValue();
        if ($key = array_search(['target_id' => $term->id()], $field_values)) {
          $user->get('field_sas_territoire')->removeItem($key);
        }
      }
      $user->save();
      $context['sandbox']['progress']++;
    }

  }

  /**
   * Get all cities terms with filtered by field_verified value.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Term taxo.
   * @param bool $countQuery
   *   Flag a count query.
   *
   * @return array|int
   *   Uids array or uids count if count query is TRUE.
   */
  protected static function getUserUidsDeleteTerritory(TermInterface $term, bool $countQuery = FALSE) {
    $query = \Drupal::entityQuery('user')->accessCheck()
      ->condition('roles', SasUserConstants::SAS_USER_UPDATE_TERRITORY, 'IN')
      ->condition('field_sas_territoire', $term->id());
    if ($countQuery) {
      $query->count();
    }
    return $query->execute();
  }

  /**
   * Update users finished batch process.
   *
   * Callback for batch_set().
   *
   * This callback may be specified in a batch to perform clean-up operations,
   * or to analyze the results of the batch operations.
   *
   * @param $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param $results
   *   The value set in $context['results'] by callback_batch_operation().
   */
  public static function deleteUserFinished($success, $results) {
    $message = "la mise à jour des utilisateurs a échoué.";
    $type = MessengerInterface::TYPE_ERROR;
    if ($success) {
      $message = t(
        '@count utilisateurs mis à jour.', [
          '@count' => $results['count'],
        ]
      );
      $type = MessengerInterface::TYPE_STATUS;
    }

    \Drupal::messenger()->addMessage($message, $type);
  }

}
