<?php

namespace Drupal\sas_snp\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\sas_geolocation\Service\SasTimezoneHelper;

/**
 * Class SnpUnavailabilityHelper.
 *
 * Helper providing snp unavailability.
 *
 * @package Drupal\sas_snp\Service
 */
class SnpUnavailabilityHelper implements SnpUnavailabilityHelperInterface {

  /**
   * SnpUnavailabilityHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    SasTimezoneHelper $sas_timezone_helper
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->sasTimezoneHelper = $sas_timezone_helper;
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function isInVacationNextThreeDays(NodeInterface $node): bool {

    if (
      !$node->get('field_sas_time_snp_active')->isEmpty() &&
      $node->get('field_sas_time_snp_active')->first()->getValue()['value']
    ) {
      return TRUE;
    }

    if (!$node->get('field_sas_time_slot_vacations')->isEmpty()) {
      $vacationSlots = $node->get('field_sas_time_slot_vacations')->getValue();
      $datetime1 = new DrupalDateTime('now');
      $timestampDay1 = $datetime1->getTimestamp();

      $datetime2 = (new DrupalDateTime('now'))->add(new \DateInterval('P1D'));
      $timestampDay2 = $datetime2->getTimestamp();

      $datetime3 = (new DrupalDateTime('now'))->add(new \DateInterval('P2D'));
      $timestampDay3 = $datetime3->getTimestamp();
      foreach ($vacationSlots as $slot) {
        if (isset($slot['value']) && isset($slot['end_value'])) {

          $startDate = new DrupalDateTime($slot['value']);
          $start = $startDate->getTimestamp();

          $endDate = new DrupalDateTime($slot['end_value']);
          $end = $endDate->getTimestamp();

          $day1 = $timestampDay1 >= $start && $timestampDay1 <= $end;
          $day2 = $timestampDay2 >= $start && $timestampDay2 <= $end;
          $day3 = $timestampDay3 >= $start && $timestampDay3 <= $end;
          if ($day1 && $day2 && $day3) {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   *
   * @return array
   */
  public function getPsNidsWithUnavailabilityInNextThreeDays(): array {

    $date = new \DateTimeImmutable();
    $startDate = $date->format('Y-m-d');
    $endDate = $date->modify('+2 days')->format('Y-m-d');

    $query = $this->database->select('node__field_sas_time_slot_ref', 'a')
      ->fields('a', ['field_sas_time_slot_ref_target_id']);

    // Jointure avec node__field_sas_time_slot_vacations sur entity_id.
    $query->leftJoin('node__field_sas_time_slot_vacations', 'v', 'v.entity_id = a.entity_id');
    // Jointure avec node__field_sas_time_snp_active.
    $query->leftJoin('node__field_sas_time_snp_active', 'act', 'act.entity_id = a.entity_id');

    // AND condition pour les dates.
    $dateCondition = $query->andConditionGroup();
    $dateCondition->condition('v.field_sas_time_slot_vacations_value', $endDate, '<=')
      ->condition('v.field_sas_time_slot_vacations_end_value', $startDate, '>=');

    // OR qui inclut la condition des dates et la condition field_sas_time_snp_active_value.
    $orCondition = $query->orConditionGroup();
    $orCondition->condition('act.field_sas_time_snp_active_value', 1)
      ->condition($dateCondition);

    $query->condition($orCondition);

    return $query->execute()->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function getUnavalaibilities(array $nodes): array {

    $nids = array_column($nodes, 'nid');
    try {
      /** @var \Drupal\node\NodeInterface $full_nodes */
      $full_nodes = $this->entityTypeManager->getStorage('node')
        ->loadMultiple($nids);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return $nodes;
    }

    $query = $this->database->select('node__field_sas_time_slot_ref', 'stsr');
    $query->fields('stsr', ['entity_id', 'field_sas_time_slot_ref_target_id']);

    $query->leftJoin('node__field_sas_time_slot_vacations', 'stsv', 'stsv.entity_id = stsr.entity_id');
    $query->leftJoin('node__field_sas_time_snp_active', 'stss', 'stss.entity_id = stsr.entity_id');
    $query->addField('stss', 'field_sas_time_snp_active_value', 'snp_active');
    $query->addField('stsv', 'field_sas_time_slot_vacations_value', 'start_vac');
    $query->addField('stsv', 'field_sas_time_slot_vacations_end_value ', 'end_vac');

    $query->condition('stsr.field_sas_time_slot_ref_target_id', $nids, 'IN');

    $response = $query->execute()->fetchAll();

    foreach ($response as $vacation) {
      if ($vacation->snp_active) {
        unset($nodes[$vacation->field_sas_time_slot_ref_target_id]['slots']);
        continue;
      }
      $timezone = $this->sasTimezoneHelper->getPlaceTimezone($full_nodes[$vacation->field_sas_time_slot_ref_target_id]);
      $timezone = new \DateTimeZone($timezone === 'Europe/Paris' ? 'CET' : $timezone);
      $start_vac = new DrupalDateTime(
        time: $vacation->start_vac,
        timezone: $timezone
      );
      $end_vac = new DrupalDateTime(
        time: $vacation->end_vac,
        timezone: $timezone
      );
      foreach ($nodes[$vacation->field_sas_time_slot_ref_target_id]['slots'] as $key => $slot) {
        $slotDate = \DateTime::createFromFormat(
          format: DATE_ATOM,
          datetime: $slot['real_date']
        );
        if ($slotDate >= $start_vac->getPhpDateTime() && $slotDate < $end_vac->getPhpDateTime()) {
          unset($nodes[$vacation->field_sas_time_slot_ref_target_id]['slots'][$key]);
        }
      }
    }

    return $nodes;
  }

}
