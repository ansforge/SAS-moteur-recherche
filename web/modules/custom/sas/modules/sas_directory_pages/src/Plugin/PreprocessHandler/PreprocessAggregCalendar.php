<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;

/**
 * Preprocessing PS aggregator calendar.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_aggreg_calendar",
 *  label = @Translation("Preprocess PS aggregator calendar"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = -210
 * )
 */
class PreprocessAggregCalendar extends PreprocessHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!isset($this->context['is_interfaced_aggregator']) || $this->context['is_interfaced_aggregator'] !== TRUE) {
      return;
    }

    $calendar_data = [];
    foreach ($this->variables['items'] as &$item) {
      if (isset($item['aggregator_slot'])) {
        $item_calendar_data = $item['aggregator_slot'];
        // When there is not slot the api sends "Pas de créneau disponible".
        if (!is_array($item_calendar_data)) {
          $ts = time();
          $item_calendar_data = [];
          for ($i = 0; $i <= 2; $i++) {
            $item_calendar_data[date("d-m", $ts + $i * 86400)] = [];
          }
        }
        $item['aggregator_calendar'] = $item_calendar_data;
        $calendar_data[$item['nid']] = $item_calendar_data;
      }
    }
    $this->variables['#attached']['drupalSettings']['aggreg-ps-calendar'] = $calendar_data;
    // $this->variables['#attached']['library'][] = 'sas_vuejs/aggreg-ps-calendar';
  }

}
