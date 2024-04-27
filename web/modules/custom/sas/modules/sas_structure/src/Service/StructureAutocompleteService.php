<?php

namespace Drupal\sas_structure\Service;

use Drupal\sas_structure\Enum\StructureConstant;

/**
 * Class StructureAutocompleteService.
 *
 * Provide service to manage autocomplete for structure.
 *
 * @package Drupal\sas_structure\Service
 */
class StructureAutocompleteService implements StructureAutocompleteServiceInterface {

  /**
   * @var \Drupal\sas_structure\Service\FinessStructureHelperInterface
   */
  protected FinessStructureHelperInterface $finessStructureHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHerlper;

  public function __construct(
    FinessStructureHelperInterface $finess_structure_helper,
    SosMedecinHelperInterface $sos_medecin_helper
  ) {
    $this->finessStructureHelper = $finess_structure_helper;
    $this->sosMedecinHerlper = $sos_medecin_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function structureAutocomplete(string $type, string $search): array {
    $results = [];

    if ($type === StructureConstant::STRUCTURE_TYPE_SOS_MEDECIN) {
      $associations = $this->sosMedecinHerlper->getAssociationList($search);
      if (!empty($associations)) {
        foreach ($associations as $siret => $association_name) {
          $results[] = [
            'id_type' => StructureConstant::ID_TYPE_SIRET,
            'id_structure' => $siret,
            'title' => $association_name,
          ];
        }
      }
    }
    else {
      $structures = $this->finessStructureHelper->searchStructures($type, $search);
      if (!empty($structures)) {
        foreach ($structures as $structure) {
          $results[] = [
            'id_type' => StructureConstant::ID_TYPE_FINESS,
            'id_structure' => $structure['finess'],
            'title' => $structure['title'],
          ];
        }
      }
    }

    return $results;
  }

}
