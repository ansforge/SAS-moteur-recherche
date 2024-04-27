<?php

namespace Drupal\sas_snp\Service;

/**
 * Class SearchResultFormatter.
 *
 * Service to format SAS-API response containing slots for search results page.
 *
 * @package Drupal\sas_snp\Service
 */
class SnpSlotsFormatter implements SnpSlotsFormatterInterface {

  /**
   * {@inheritDoc}
   */
  public function orderByTimestamp(array $results): array {
    $cards = [];

    foreach ($results as $entry) {
      $cards[$entry['nid']] = $entry;

      if (!empty($entry['slots'])) {
        foreach ($entry['slots'] as $x => $slot) {
          $slot['timestamp'] = strtotime($slot['real_date']);
          $cards[$entry['nid']]['slots'][$x] = $slot;
        }

        usort($cards[$entry['nid']]['slots'], static function ($a, $b) {
          return $a['timestamp'] <=> $b['timestamp'];
        });

        $cards[$entry['nid']]['timestamp'] = $cards[$entry['nid']]['slots'][0]['timestamp'] ?? '';
      }
    }

    return $cards;
  }

}
