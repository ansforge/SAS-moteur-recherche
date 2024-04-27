<?php

namespace Drupal\sas_snp\Batch;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_snp\Model\SasAvailability;

/**
 * Class batch UpdateSnpBatch.
 */
class UpdateSnpBatch {

  /**
   * Operation callback.
   *
   * @param array $schedules
   *   Array of entity node and schedule ids.
   * @param array|\DrushBatchContext $context
   *   Standard batch context.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public static function updateSnp(array $schedules, array $ids, array|\DrushBatchContext &$context) {
    if (empty($schedules)) {
      return;
    }

    $availability_manager = \Drupal::service('sas_snp.availability_manager');

    // Start day is today at midnight.
    $start_date = new \DateTimeImmutable(
      datetime: 'today',
      timezone: new \DateTimeZone('+0100')
    );
    // End date is two day after at 23:59:59.
    $end_date = $start_date
      ->modify('+2 days +23 hours +59 minutes +59 seconds')
      ->format(DATE_ATOM);
    $start_date = $start_date->format(DATE_ATOM);

    $node_ids = array_keys($schedules);
    $availabilities = $availability_manager->loadByNids($node_ids);

    $response = \Drupal::service('sas_api_client.service')->sas_api('get_slots_by_schedule_ids', [
      'query' => [
        'start_date' => $start_date,
        'end_date' => $end_date,
      ],
      'body' => [
        'schedule_ids' => array_column($schedules, 'schedule_id'),
      ],
    ]);

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }

    foreach ($schedules as $schedule) {
      $node_id = $schedule->entity_id;
      $schedule_id = $schedule->schedule_id;
      $participation_sas_via = $schedule->participation_sas_via;

      // Find the corresponding availability object in the loaded array.
      $availability = NULL;
      if (isset($availabilities[$node_id])) {
        $availability = $availabilities[$node_id];
      }

      if (!$availability) {
        // If SASAvailability doesn't exist, create it using data from $slice.
        $has_snp = 0;
        $is_interfaced = 0;

        $availability = new SasAvailability($node_id, $has_snp, $is_interfaced);
        $availability_manager->insert($availability);
      }

      if ($availability instanceof SasAvailability) {
        $has_snp = $availability->isHasSnp();

        if (!$has_snp && !empty($response[$schedule_id]) && !in_array($node_id, $ids)) {
          $availability->setHasSnp(1);
        }

        if ($participation_sas_via == SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN ||
          ($has_snp && empty($response[$schedule_id])) ||
          in_array($node_id, $ids)) {
          $availability->setHasSnp(0);
        }

        // Update the SASAvailability object in the database.
        $availability_manager->update($availability);
      }
      // Force indexation of professional sheet.
      \Drupal::service('sas_search_index.helper')->indexSpecificItem($node_id);
      $context['results']['count']++;
    }
  }

  /**
   * Finish Callback.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   */
  public static function finished(bool $success, array $results) {
    $message = t('An error occurred while processing');
    $type = MessengerInterface::TYPE_ERROR;
    if ($success) {
      $message = t('@count plages horaires processed.', [
        '@count' => $results['count'],
      ]);
      $type = MessengerInterface::TYPE_STATUS;
    }
    \Drupal::messenger()
      ->addMessage($message, $type);
  }

}
