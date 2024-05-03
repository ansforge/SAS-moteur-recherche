<?php

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\HealthInstitution;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;

/**
 * HealthInstitutionSas class.
 */
class HealthInstitutionSas extends HealthInstitution implements SasSnpHelperInterface {

  use SasSnpHelperTrait;

  /**
   * Get list of intervention zone from associated health offer.
   *
   * @return array<\Drupal\taxonomy\Entity\Term>
   */
  public function getInterventionZoneList(): array {
    $intervention_zone_list = [];

    $care_deals = $this->getCareDeals();
    if (!empty($care_deals)) {
      foreach ($care_deals as $care_deal) {
        if ($care_deal instanceof CareDealsSas) {
          $intervention_zone_list = array_merge($intervention_zone_list, $care_deal->getInterventionZoneList());
        }
      }
    }

    return $intervention_zone_list;
  }

  public function getInterventionCityInseeList(): array {
    $intervention_zone_list = $this->getInterventionZoneList();

    if (empty($intervention_zone_list)) {
      return [];
    }

    $insee_list = [];
    foreach ($intervention_zone_list as $intervention_zone) {
      if ($intervention_zone->bundle() !== 'cities') {
        continue;
      }

      if (
        $intervention_zone->hasField('field_insee')
        && !$intervention_zone->get('field_insee')->isEmpty()
      ) {
        $insee_list[] = $intervention_zone->get('field_insee')->value;
      }
    }

    return $insee_list;
  }

}
