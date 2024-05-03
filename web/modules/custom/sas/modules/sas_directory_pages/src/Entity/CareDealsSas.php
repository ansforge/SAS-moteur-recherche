<?php

declare(strict_types = 1);

namespace Drupal\sas_directory_pages\Entity;

use Drupal\sante_directory_pages\Entity\CareDeals;

/**
 * CareDealsSas class.
 */
class CareDealsSas extends CareDeals {

  public function getInterventionZoneList(): array {
    if (
      !$this->hasField('field_zone_intervention')
      || $this->get('field_zone_intervention')->isEmpty()
    ) {
      return [];
    }

    $intervention_zone_list = [];

    /** @var \Drupal\paragraphs\Entity\Paragraph[] $intervention_zone_entities */
    $intervention_zone_entities = $this->get('field_zone_intervention')->referencedEntities();
    if (!empty($intervention_zone_entities)) {
      foreach ($intervention_zone_entities as $intervention_zone) {
        if (
          $intervention_zone->hasField('field_zone_de_division')
          && !$intervention_zone->get('field_zone_de_division')->isEmpty()
        ) {
          $intervention_zone_term = $intervention_zone->get('field_zone_de_division')->referencedEntities();
          $intervention_zone_list[] = reset($intervention_zone_term);
        }
      }
    }

    return $intervention_zone_list;
  }

  /**
   * Get all phone numbers for current care deals content.
   *
   * @return array
   */
  public function getCareDealsPhones() {
    $phones = [];

    if ($this->get('field_phone_number_paragraphs')->isEmpty()) {
      return [];
    }

    /** @var \Drupal\paragraphs\Entity\Paragraph[] $phonesParagraphs */
    $phonesParagraphs = $this->get('field_phone_number_paragraphs')->referencedEntities();
    foreach ($phonesParagraphs as $phonesParagraph) {
      if ($phonesParagraph->get('field_phone_number_num')->isEmpty()) {
        continue;
      }

      $phones[] = $phonesParagraph->get('field_phone_number_num')->value;
    }

    return $phones;

  }

}
