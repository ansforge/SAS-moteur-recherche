<?php

namespace Drupal\sas_directory_pages\Entity\Feature;

/**
 * AggregLinkTrait trait.
 */
trait AggregLinkTrait {

  /**
   * Check if the practitioner exists in the aggregator api.
   *
   * @return bool|null
   *   TRUE if in the aggregator database.
   *   FALSE if not int the aggregator database.
   *   null if we could not determine.
   */
  public function isAggregPractitionerExist() {
    $pro_id = $this->getAggregPsProId();
    if (!$pro_id) {
      // Arbitrage 2022 06 16 : Si aucun id on considère comme non interfacé (Ex. sources ROR)
      return FALSE;
    }
    /** @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager $sas_client */
    $sas_client = \Drupal::service('sas_api_client.service');
    $response = $sas_client->aggregator('practitioner', [
      "tokens" => [
        "id" => $pro_id,
      ],
    ]);
    if (!$response || !isset($response['exist_in_directory'])) {
      return NULL;
    }
    return $response['exist_in_directory'];
  }

  /**
   * Get PS identifier for use with aggregator API.
   *
   * @return string|null
   *   the pro_id to use to interact with the aggreg api.
   */
  public function getAggregPsProId() {
    if ($this->hasField('field_identifiant_rpps')) {
      $field_identifiant_rpps = $this->get('field_identifiant_rpps')->first();
      if ($field_identifiant_rpps) {
        return '8' . $field_identifiant_rpps->value;
      }
    }
    if ($this->hasField('field_personne_adeli_num')) {
      $field_personne_adeli_num = $this->get('field_personne_adeli_num')->first();
      if ($field_personne_adeli_num) {
        return '0' . $field_personne_adeli_num->value;
      }
    }
    return NULL;
  }

}
