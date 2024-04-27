<?php

namespace Drupal\sas_snp\Batch;

/**
 * Class batch UpdateSnpTimezoneBatch.
 */
class UpdateSnpTimezoneBatch {

  /**
   * Operation callback.
   *
   * @param array $chunk
   *   Array of schedule ids and node ids.
   * @param array|\DrushBatchContext $context
   *   Standard batch context.
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public static function updateSnpSlots(array $chunk, array|\DrushBatchContext &$context) {
    $node_ids = array_column($chunk, 'node_id');

    try {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($node_ids);
    }
    catch (\Exception $e) {
      return;
    }

    $schedule_ids = array_column($chunk, 'schedule_id');

    $slots = \Drupal::service('sas_api_client.service')->sas_api('get_slots_by_schedule_ids', [
      'body' => [
        'schedule_ids' => $schedule_ids,
      ],
    ]);

    if (empty($slots)) {
      return;
    }

    $schedulesData = [];
    foreach ($chunk as $item) {
      if (empty($nodes[$item['node_id']]) || empty($item['schedule_id'])) {
        continue;
      }

      $schedulesData[$item['schedule_id']] = [
        'id' => (int) $item['schedule_id'],
        'timezone' => \Drupal::service('sas_geolocation.timezone')->getPlaceTimezone($nodes[$item['node_id']]),
      ];
    }

    if (!empty($schedulesData)) {
      \Drupal::service('sas_api_client.service')->sas_api('update_schedule_bulk', [
        'body' => $schedulesData,
      ]);
    }

    $slotsData = [];
    foreach ($slots as $slot) {
      if (!isset($schedulesData[$slot['schedule']['id']]['timezone'])) {
        continue;
      }

      $date = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $slot['date']);

      $start_hours_minutes = str_pad($slot['start_hours'], 4, '0', STR_PAD_LEFT);
      $end_hours_minutes = str_pad($slot['end_hours'], 4, '0', STR_PAD_LEFT);

      $start_parts = str_split($start_hours_minutes, 2);
      $start_hours = $start_parts[0];
      $start_minutes = $start_parts[1];

      $end_parts = str_split($end_hours_minutes, 2);
      $end_hours = $end_parts[0];
      $end_minutes = $end_parts[1];

      try {
        $timezone = $schedulesData[$slot['schedule']['id']]['timezone'] === 'Europe/Paris' ? 'CET' : $schedulesData[$slot['schedule']['id']]['timezone'];

        $start_date = new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
        $start_date->setTime($start_hours, $start_minutes);
        $start_date->setTimezone(new \DateTimeZone('UTC'));

        $end_date = new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
        $end_date->setTime($end_hours, $end_minutes);
        $end_date->setTimezone(new \DateTimeZone('UTC'));
      }
      catch (\Exception $e) {
        continue;
      }

      $slotsData[] = [
        'id' => (int) $slot['id'],
        'date' => $start_date->format(\DateTimeInterface::ATOM),
        'start_hours' => (int) $start_date->format('Gi'),
        'end_hours' => (int) $end_date->format('Gi'),
      ];
    }

    if (!empty($slotsData)) {
      \Drupal::service('sas_api_client.service')->sas_api('update_slot_bulk', [
        'body' => $slotsData,
      ]);
    }
  }

  /**
   * Finish Callback.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public static function finished(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('All slots processed.'));
    }
    else {
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args', [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0], TRUE),
        ])
      );
    }
  }

}
