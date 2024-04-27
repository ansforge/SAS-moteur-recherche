<?php

namespace Drupal\sas_user\Batch;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\User;

/**
 * Batch Update users in update Territory.
 */
class SasTerritoryUsersUpdate {

  /**
   * Get a batch operations Update users in update Territory.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Terms taxo.
   *
   * @return array
   *   The built batch object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function getBatch(TermInterface $term): array {

    $postal_codes = $term->get('field_sas_postal_codes')->value;
    $operations = [];

    $operations[] = [
      [static::class, 'territoryUserUpdate'],
      [$postal_codes, $term, 'add'],
    ];

    if (!empty($term->original)) {
      $postal_codes_original = $term->original->get('field_sas_postal_codes')->value;
      $delta_code_postal = implode(',', array_diff(explode(',', $postal_codes_original), explode(',', $postal_codes)));
      if (!empty($delta_code_postal)) {
        $operations[] = [
          [static::class, 'territoryUserUpdate'],
          [$delta_code_postal, $term, 'delete'],
        ];
      }
    }

    return [
      'title' => t('Mise à jours des utilisateurs'),
      'operations' => $operations,
      'finished' => [__CLASS__, 'updateUserFinished'],
    ];

  }

  /**
   * Update users in update Territory.
   *
   * @param string $postal_codes
   *   Code postal in term.
   * @param \Drupal\taxonomy\TermInterface $term
   *   Terms taxo.
   * @param string $method
   *   Operation term.
   * @param array $context
   *   Batch context array.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function territoryUserUpdate(string $postal_codes, TermInterface $term, string $method, array &$context) {

    /* Gestion du context['sandbox'] */
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['results']['count'] = $context['sandbox']['max'] = self::getUserUids($postal_codes, TRUE);
      $context['sandbox']['users'] = self::getUserUids($postal_codes);
    }

    $users = User::loadMultiple(array_slice($context['sandbox']['users'], $context['sandbox']['progress'], 50));
    foreach ($users as $user) {

      if ($method === 'add') {
        if (!in_array(['target_id' => $term->id()], $user->get('field_sas_territoire')
          ->getValue())) {
          $user->get('field_sas_territoire')
            ->appendItem(['target_id' => $term->id()]);
        }
      }
      else {
        if (!$user->get('field_sas_territoire')->isEmpty()) {
          $field_values = $user->get('field_sas_territoire')->getValue();
          $key = array_search(['target_id' => $term->id()], $field_values);
          if ($key !== FALSE) {
            $user->get('field_sas_territoire')->removeItem($key);
          }
        }
      }
      $user->save();
      $context['sandbox']['progress']++;
    }

    $context['finished'] = 1;
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['message'] = t(
        '@count/@max utilisateurs mis à jour.', [
          '@count' => $context['sandbox']['progress'],
          '@max' => $context['sandbox']['max'],
        ]
      );
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Get all codes postaux referenced in users.
   *
   * @param string $postal_codes
   *   Postal codes.
   * @param bool $countQuery
   *   Flag a count query.
   *
   * @return array|int
   *   Tids array or tids count if count query is TRUE.
   */
  protected static function getUserUids(string $postal_codes, bool $countQuery = FALSE) {
    $query = \Drupal::entityQuery('user')->accessCheck()
      ->condition('roles', SasUserConstants::SAS_USER_UPDATE_TERRITORY, 'IN')
      ->condition('field_sas_codes_postaux', str_replace(',', '|', $postal_codes), 'REGEXP');
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
  public static function updateUserFinished($success, $results) {
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
