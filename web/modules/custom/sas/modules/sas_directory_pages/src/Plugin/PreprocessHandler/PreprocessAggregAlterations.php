<?php

namespace Drupal\sas_directory_pages\Plugin\PreprocessHandler;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\sante_directory_pages\Plugin\PreprocessHandlerBase;
use Drupal\sas_directory_pages\Service\SasDirectoryAggregServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocessing PS and alter data if interfaced to an editor.
 *
 * @package Drupal\sas_directory_pages\Plugin\PreprocessHandler
 *
 * @PreprocessHandler(
 *  id = "preprocess_aggreg_alterations",
 *  label = @Translation("Preprocess aggreg alterations"),
 *  bundles = {
 *    "professionnel_de_sante",
 *  },
 *  themes = {
 *    "annuaire_professionnel_de_sante"
 *  },
 *  context = "sas",
 *  priority = -200
 * )
 */
class PreprocessAggregAlterations extends PreprocessHandlerBase {

  /**
   * The aggreg helper service.
   *
   * @var \Drupal\sas_directory_pages\Service\SasDirectoryAggregServiceInterface
   */
  private SasDirectoryAggregServiceInterface $sasDirectoryAggregator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sasDirectoryAggregator = $container->get('sas_directory_pages.aggreg_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Recherche des alterations de lieux et créneaux si le ps est interfacé
    // Dans un tel cas nous sommes en chargement de contenu ajax.
    if (!isset($this->context['is_interfaced_aggregator']) || $this->context['is_interfaced_aggregator'] !== TRUE) {
      return;
    }

    $places_list = [];

    foreach ($this->variables['items'] as $item) {
      // Aggreg API requested default values.
      $place = [
        "rpps" => '',
        "adeli" => '',
        "finess" => '',
        "siret" => '',
        "rpps_rang" => '',
        "cp" => '',
        "address" => '',
        "phone" => [
          '',
        ],
      ];

      $phone = $item['phones'] ?? [];
      if (!empty($phone)) {
        $place['phone'] = $phone;
      }

      $place_values = [
        "rpps" => $item['id_rpps'] ?? NULL,
        "adeli" => $item['adeli'][0] ?? NULL,
        "finess" => $item['finess_value'] ?? NULL,
        "siret" => $item['siret_value'] ?? NULL,
        "rpps_rang" => $item['rpps_rang'] ?? NULL,
        "cp" => $item["raw_address"]["postal_code"] ?? NULL,
        "address" => $item["raw_address"]["full_address"] ?? NULL,
      ];
      $place = array_merge($place, array_filter($place_values));

      $places_list[$item['nid']] = $place;
    }

    $actions = $this->sasDirectoryAggregator->getPractitionerSlots($places_list);

    // Store actions keys before sorting.
    foreach ($actions as $key => $action) {
      $actions[$key]['key'] = $key;
    }
    // Sort actions to do delete at the end
    // The order is not guaranteed by the API.
    usort($actions, function ($a, $b) {
      return $this->getActionPriority($b) <=> $this->getActionPriority($a);
    });

    // Doing actions.
    foreach ($actions as $action) {
      switch ($action['action']) {

        case "create";
          $this->createItem($action);
          break;

        case "update":
          $this->updateItem($action);
          break;

        case "delete":
          $this->deleteItem($action);
          break;
      }
    }

    // Clean items.
    $this->variables['items'] = array_values($this->variables['items']);
    // Empty context/nodeItemsByNid that is now unusable
    // and may not be used by another plugin coming after this one.
    $this->context["nodeItemsByNidForPsPage"] = $this->context["nodeItemsByNid"];
    unset($this->context["nodeItemsByNid"]);

    // Fixing multiNode value.
    $this->variables['multiNode'] = boolval(count($this->variables['items']) - 1);
  }

  private function getActionPriority($action) {
    switch ($action['action']) {
      case "create";
        return 2;

      case "update":
        return 1;

      case "delete":
      default:
        return 0;
    }
  }

  /**
   * Helper to get the item key corresponding to a nid.
   *
   * @param int $nid
   *
   * @return int|bool
   *   The item key if found or false.
   */
  private function findItemKey($nid) {
    foreach ($this->variables['items'] as $key => $item) {
      if ($item['nid'] == $nid) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * Helper to get the untouched item key corresponding to a nid.
   *
   * @param int $nid
   *
   * @return int|bool
   *   The item key if found or false.
   */
  private function findUntouchedItemKey($nid) {
    foreach ($this->variables['items'] as $key => $item) {
      if ($item['nid'] == $nid && !isset($item['aggregator_location_id'])) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * Update an item from action.
   *
   * @param array $action
   */
  private function updateItem($action) {
    $item_key = $this->findItemKey($action['key']);
    if ($item_key !== FALSE) {
      $item = &$this->variables["items"][$item_key];

      $item["aggregator_location_id"] = $action["id"];
      $item["aggregator_action"] = "update";
      $item["aggregator_specialities"] = $action['practitioner']['specialities'] ?? NULL;

      $this->setItemSlots($item, $action);

      $this->setItemDataPublicationDate($item);
    }
  }

  /**
   * Create an item from action.
   *
   * @param array $action
   */
  private function createItem($action) {
    if (!isset($action['nid'])) {
      throw new PluginException("Missing a nid target to copy from in aggregator api response.");
    }

    $source_nid = $action['nid'];
    $source_item_key = $this->findItemKey($source_nid);

    $new_item = $this->variables["items"][$source_item_key];
    foreach (array_keys($new_item) as $key) {
      $new_item[$key] = NULL;
    }

    $new_item["aggregator_location_id"] = $action["id"];
    $new_item["aggregator_action"] = "create";
    $new_item["aggregator_specialities"] = $action['practitioner']['specialities'] ?? NULL;

    // The nid drupal.
    $new_item['nid'] = $action['nid'];

    $this->setItemPhone($new_item, $action);

    $this->setItemAdresse($new_item, $action);

    $this->setItemSlots($new_item, $action);

    $this->setItemDataPublicationDate($new_item);

    $this->variables['items'][] = $new_item;
  }

  /**
   * Delete an item from action.
   *
   * @param array $action
   */
  private function deleteItem($action) {
    $item_key = $this->findUntouchedItemKey($action['key']);
    if ($item_key !== FALSE) {
      unset($this->variables['items'][$item_key]);
    }
  }

  /**
   * Set an item "adresse" from aggreg data.
   *
   * @param &array $item
   * @param array $action
   */
  private function setItemAdresse(&$item, $action) {
    $adresse = '';
    if (isset($action['address']) && is_array($action['address'])) {
      $keys = ['line', 'cp', 'city'];
      $intersect = array_flip($keys);
      $address = array_intersect_key($action['address'], $intersect);
      $item['raw_address']['address_line1'] = $address['line'];
      $item['raw_address']['postal_code'] = $address['cp'];
      $item['raw_address']['locality'] = $address['city'];
      $adresse .= trim(implode(' ', $address));
    }
    $item['adresse'] = $adresse;
  }

  /**
   * Set an item slots from aggreg data.
   *
   * @param &array $item
   * @param array $action
   */
  private function setItemSlots(&$item, $action) {
    if (isset($action['slot'])) {
      $item['aggregator_slot'] = $action['slot'];
    }
  }

  /**
   * Set an item phone from aggreg data.
   *
   * @param &array $item
   * @param array $action
   */
  private function setItemPhone(&$item, $action) {
    $item['phones'] = [];
    if (isset($action['practitioner']['phone']) && trim($action['practitioner']['phone'])) {
      $item['phones'] = [$action['practitioner']['phone']];
    }
  }

  /**
   * Set an item data publication date info.
   *
   * @param &array $item
   */
  private function setItemDataPublicationDate(&$item) {
    $item['publication_date'] = ['#markup' => '<p>' . 'Dernière mise à jour : ' . date("d/m/Y") . '</p>'];
  }

}
