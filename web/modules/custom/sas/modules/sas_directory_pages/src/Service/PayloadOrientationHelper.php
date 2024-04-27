<?php

namespace Drupal\sas_directory_pages\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface;

/**
 * Class PayloadOrientationHelper.
 *
 * Helper providing data on Payload Orientation.
 *
 * @package Drupal\sas_directory_pages\Service
 */
class PayloadOrientationHelper implements PayloadOrientationHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * Taxonomy term storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $termStorage;

  /**
   * The term.territory service.
   */
  protected SasGetTermCodeCitiesInterface $termTerritory;

  /**
   * Constructs a new Controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface $term_territory
   *   The term.territory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
                              SasGetTermCodeCitiesInterface $term_territory
  ) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->termTerritory = $term_territory;
  }

  /**
   * Return Payload orientation.
   *
   * @param $node
   *   Node object.
   *
   * @return array
   *   Return Payload
   */
  public function payload($node): array {
    $territory = $this->termTerritory->sasGetTerritoriesFromNode($node);
    $territory_ids = $this->termStorage->loadMultiple($territory);

    foreach ($territory_ids as $territory_id) {
      $id = $territory_id->get('field_sas_api_id_territory')->value;
    }

    $code_postal = $node->get('field_address')
      ->first()
      ->getValue()['postal_code'];
    if (!empty($code_postal)) {
      $department = $this->getDptFromPostalCode($code_postal);
      $department_id = $department->get('field_department_id')->value;
      $department_name = $department->getName();
    }

    if ($node->hasField('field_identifiant_finess')) {
      $structure_finess = $node->get('field_identifiant_finess')
        ->first()->value;
    }

    $type = 2;
    $structure_siret = $node->get('field_identif_siret')->first()->value;
    $telephones = $node->getFreeAccessPhones();

    switch ($node->bundle()) {
      case "professionnel_de_sante";
        $structure_type = $node->get('field_profession')->entity->getName();
        $type = 1;
        $effector_rpps = $node->get('field_identifiant_rpps')->first()->value;
        $effector_adeli = $node->get('field_personne_adeli_num')->first()->value;
        $effector_speciality = $node->get('field_specialite')->entity->getName();
        break;

      case "service_de_sante":
        $structure_type = $node->get('field_type_de_service_de_sante')->entity->getName();
        break;

      case "finess_institution":
        $structure_type = $node->get('field_finess_establishment_type')->entity->getName();
        break;

      case "health_institution":
        $structure_type = $node->get('field_establishment_type')->entity->getName();
        break;

      default:
        // Do nothing.
    }

    return [
      'type' => $type,
      'effector_rpps' => $effector_rpps ?? NULL,
      'effector_adeli' => $effector_adeli ?? NULL,
      'structure_finess' => $structure_finess ?? NULL,
      'effector_speciality' => $effector_speciality ?? NULL,
      'phone_number' => $telephones ?? [],
      'name' => $node->getTitle() ?? '',
      'address' => $node->get('field_address')
        ->first()
        ->getValue()['full_address'] ?? '',
      'structure_siret' => $structure_siret ?? NULL,
      'county' => $department_name ?? '',
      'county_number' => $department_id ?? '',
      'structure_type' => $structure_type ?? NULL,
      'effector_is_sas' => TRUE,
      'effector_territory' => [
        'id' => $id ?? '',
      ],
    ];
  }

  /**
   * Return department from postalcode.
   *
   * @param $code_postal
   *   codepostal adress.
   *
   * @return object
   *   Return department
   */
  public function getDptFromPostalCode($code_postal) {
    $department = NULL;

    if (!empty($code_postal) && strlen($code_postal) == 5) {
      $depCode = str_starts_with($code_postal, '97') ? substr($code_postal, 0, 3) : substr($code_postal, 0, 2);

      // Corsica case.
      if (str_starts_with($code_postal, '20')) {
        $cities = $this->termStorage->loadByProperties([
          'field_postal_code' => $code_postal,
          'vid' => 'cities',
        ]);

        if (!empty($cities)) {
          $city = reset($cities);
          $insee_code = $city->get('field_insee')->value;
        }
        if (!empty($insee_code)) {
          $depCode = substr($insee_code, 0, 2);
        }
      }

      $dpts = $this->termStorage->loadByProperties([
        'field_department_id' => $depCode,
      ]);

      if (!empty($dpts)) {
        $department = reset($dpts);
      }
    }

    return $department;
  }

}
