<?php

namespace Drupal\sas_directory_pages\Entity\Feature;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_snp\Enum\SnpConstant;

/**
 * SasSnpHelperTrait trait
 * provides getters to acces SNP slots data & context.
 */
trait SasSnpHelperTrait {

  /**
   * Check if the entity aggreg/editor data display is disabled
   * on this particular node.
   * It can only be TRUE on PS, default to FALSE on other bundles.
   *
   * @return bool|null
   *   TRUE if disabled.
   *   FALSE if not disabled.
   *   null if we could not determine.
   */
  public function isEditorDisplayDisabled(): bool|NULL {
    if (!$this instanceof ProfessionnelDeSanteSas) {
      // Editor slots display can only be disabled on PS
      // and is always considered disabled on etab.
      return TRUE;
    }

    $place_owner = $this->getNationalId()['id'];
    if (!$place_owner) {
      return NULL;
    }
    $this->addCacheableDependency($place_owner);
    /** @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard_users_service */
    $dashboard_users_service = \Drupal::service('sas_user_dashboard.dashboard');
    /** @var \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $sas_snp_user_data */
    $sas_snp_user_data = $dashboard_users_service->sasGetEntitySasSnpUserData($place_owner['user_id']);
    if (!$sas_snp_user_data) {
      return NULL;
    }
    $this->addCacheableDependency($sas_snp_user_data);
    return $sas_snp_user_data
      ->get('editor_disabled')
      ->first()
      ->getValue()['value'];
  }

  /**
   * Check if the entity aggreg/editor data display is disabled
   * on the page of this particular node depending on its places
   * It can only be TRUE on PS, default to FALSE on other bundles.
   *
   * @return bool|null
   *   TRUE if disabled.
   *   FALSE if not disabled.
   *   null if we could not determine.
   */
  public function isEditorDisplayDisabledOnPage(): bool|NULL {
    if (!$this instanceof ProfessionnelDeSanteSas) {
      // Editor slots display can only be disabled on PS
      // and is always considered disabled on etab.
      return TRUE;
    }
    $places_nodes = $this->getDistinctAddressPlacesNodes();
    $owners = [];
    foreach ($places_nodes as $place_node) {
      $place_owner = $place_node->getNationalId()['id'];
      if (!$place_owner) {
        continue;
      }
      $owners[] = $place_owner;
    }

    if (empty($owners)) {
      return NULL;
    }

    $owners = array_unique($owners);
    if (count($owners) > 1) {
      \Drupal::messenger()->addError('Multiple distinct SAS Effecteurs found on professionnel_de_sante ' . $this->id());
      return NULL;
    }

    $owner = reset($owners);
    /** @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface $dashboard_users_service */
    $dashboard_users_service = \Drupal::service('sas_user_dashboard.dashboard');
    /** @var \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $sas_snp_user_data */
    $sas_snp_user_data = $dashboard_users_service->sasGetEntitySasSnpUserData($owner);
    if (!$sas_snp_user_data) {
      return NULL;
    }
    $this->addCacheableDependency($sas_snp_user_data);
    return $sas_snp_user_data
      ->get('editor_disabled')
      ->first()
      ->getValue()['value'];
  }

  /**
   * Get the sas_time_slots referencing the current node.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  public function getSasTimeSlots(): NodeInterface|NULL {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $query = $node_storage
      ->getQuery()->accessCheck()
      ->condition('field_sas_time_slot_ref', $this->id());
    $result = $query->execute();
    if (!empty($result) && is_array($result)) {
      return $node_storage->load(reset($result));
    }
    return NULL;
  }

  /**
   * Get the schedule id of the entity from its sas_time_slots node if exists.
   *
   * @return int|null
   *   The schedule id identifying the entity in the SAS API.
   *   null if the sas_time_slots node does not exist or schedule_id is empty
   */
  public function getScheduleId(): int|NULL {
    /** @var \Drupal\node\NodeInterface $sas_time_slots */
    $sas_time_slots = $this->getSasTimeSlots();
    if (!$sas_time_slots) {
      return NULL;
    }
    if ($sas_time_slots->get('field_sas_time_slot_schedule_id')->isEmpty()) {
      return NULL;
    }
    return $sas_time_slots->get('field_sas_time_slot_schedule_id')->first()->getValue()['value'];
  }

  /**
   * Get the info edit of the entity from its sas_time_slots node if exists.
   *
   * @return string|null
   */
  public function getInfoEdit(): string|NULL {
    /** @var \Drupal\node\NodeInterface $sas_time_slots */
    $sas_time_slots = $this->getSasTimeSlots();
    if (!$sas_time_slots) {
      return NULL;
    }
    return $sas_time_slots->get('field_sas_time_info')?->first()?->getValue()['value'];
  }

  /**
   * Get place main data.
   *
   * @return array
   *   Main data as array.
   */
  public function getPlaceData(): array {

    if (!$this instanceof Node) {
      return [];
    }

    $data = [
      'sheet_nid' => $this->id(),
      'title' => $this->getTitle(),
    ] + $this->getPlaceIds();

    if (!empty($data['finess'])) {
      $node = $this->getContentByFiness($data['finess']);
      if ($node && \Drupal::service('sas_structure.helper')->isCds($node)) {
        return [];
      }
    }

    $last_update = $this->getChangedTime();

    if ($this->hasField('field_address') && !$this->get('field_address')->isEmpty()) {
      $address_data = $this->get('field_address')->first()->getValue();
      $data['address'] = $address_data['full_address'] ?? '';
      $data['street'] = $address_data['address_line1'] ?? '';
      $data['city'] = $address_data['locality'] ?? '';
      $data['postcode'] = $address_data['postal_code'] ?? '';
    }

    if ($this->hasField('field_geolocalisation') && !$this->get('field_geolocalisation')->isEmpty()) {
      $adress_geolocalisation = $this->get('field_geolocalisation')->first()->getValue();
      $data['latitude'] = $adress_geolocalisation['lat'] ?? '';
      $data['longitude'] = $adress_geolocalisation['lon'] ?? '';
    }

    $data['phone_number'] = $this->getFreeAccessPhones();

    $sas_time_slots = $this->getSasTimeSlots();
    if (!empty($sas_time_slots)) {
      $data['timeslot_nid'] = $sas_time_slots->id();
      $data['schedule_id'] = $sas_time_slots->get('field_sas_time_slot_schedule_id')->value;
      $schedule_last_update = $sas_time_slots->getChangedTime();
      $last_update = $schedule_last_update > $last_update ? $schedule_last_update : $last_update;
    }

    $data['last_update'] = date('d/m/Y', $last_update);

    /** @var \Drupal\sas_snp\Service\SnpContentHelperInterface $snp_content_helper */
    $snp_content_helper = \Drupal::service('sas_snp.content_helper');
    $data['calendar_url'] = $snp_content_helper->getSnpContentUrl($this);

    return $data;

  }

  /**
   * Get place ids like RPPS/ADELI for effector or FINESS/SIRET for organisation.
   *
   * @return array
   *   List of ids found for this place.
   */
  public function getPlaceIds(): array {
    $ids = [];

    if ($this instanceof ProfessionnelDeSanteSas) {
      $ids['id_nat'] = $this->getNationalId();
    }

    foreach (SnpConstant::SAS_STRUCTURE_ID_FIELDS_MAPPING as $field_name => $prop_name) {
      if ($this->hasField($field_name) && !$this->get($field_name)->isEmpty()) {
        $ids[$prop_name] = $this->get($field_name)->value;
      }
    }

    return $ids;
  }

  /**
   * Get Content by finess ID.
   *
   * @param string $num_finess
   *
   * @return \Drupal\node\Entity\Node|null
   */
  public function getContentByFiness(string $num_finess): ?Node {
    $query = \Drupal::entityQuery('node')->accessCheck()
      ->condition('field_identifiant_finess', $num_finess);
    $nids = $query->execute();
    if (!empty($nids)) {
      $node_id = reset($nids);
      return Node::load($node_id);
    }
    return NULL;
  }

  /**
   * Get Field in custom table sas_snp_availability.
   */
  public function getFieldSasSnpAvailability() {
    if (!$this instanceof Node) {
      return NULL;
    }

    $query = \Drupal::database()->select(
      'sas_snp_availability',
      'sas_availability'
    );
    $query->fields('sas_availability');
    $query->condition('nid', $this->id());
    $unavailability = $query->execute()->fetchAll();

    if (!empty($unavailability)) {
      return reset($unavailability);
    }
    return NULL;
  }

}
