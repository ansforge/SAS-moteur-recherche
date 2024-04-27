<?php

declare(strict_types = 1);

namespace Drupal\sas_user_dashboard\Services;

use Drupal\sas_snp\Service\SnpContentHelperInterface;
use Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Drupal\sas_structure\Service\StructureSettingsHelperInterface;
use Drupal\user\UserInterface;

/**
 * SasDashboardSosDoctors Class.
 */
final class SasDashboardSosDoctors {

  /**
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected SnpContentHelperInterface $snpContentHelper;

  /**
   * Structure Settings Helper.
   *
   * @var \Drupal\sas_structure\Service\StructureSettingsHelperInterface
   */
  protected StructureSettingsHelperInterface $structureSettingsHelper;

  /**
   * SOS Medecin Helper.
   *
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosDoctorsIsInterfacedHelper
   */
  private SosDoctorsIsInterfacedHelper $sosDoctorsIsInterfacedHelper;

  public function __construct(
    SnpContentHelperInterface $snp_content_helper,
    StructureSettingsHelperInterface $structure_settings_helper,
    SosMedecinHelperInterface $sos_medecin_helper,
    SosDoctorsIsInterfacedHelper $sosDoctorsIsInterfacedHelper
  ) {
    $this->snpContentHelper = $snp_content_helper;
    $this->structureSettingsHelper = $structure_settings_helper;
    $this->sosMedecinHelper = $sos_medecin_helper;
    $this->sosDoctorsIsInterfacedHelper = $sosDoctorsIsInterfacedHelper;
  }

  /**
   * Get list of SOS Medecin association with their PFG.
   *
   * @param string[] $siret_list
   *   List of association siret.
   *
   * @return array
   *   List of SOS Medecin association with their list of PFG data.
   */
  public function getSosMedecinAssociationsList(array $siret_list, UserInterface $user): array {
    $association_list = [];

    foreach ($siret_list as $siret) {
      /** @var \Drupal\node\NodeInterface[] $pfg_contents */
      $pfg_contents = $this->sosMedecinHelper->getAssociationPfg($siret);
      if (!empty($pfg_contents)) {
        $pfg_list = [];

        foreach ($pfg_contents as $pfg_content) {
          $pfg_list[] = [
            'title' => $pfg_content->label(),
            'address' => $pfg_content->get('field_address')
              ->first()
              ->getValue()['full_address'],
            'telephone' => $pfg_content->get('field_telephone_fixe')->first()->getValue()['value'],
            'linkAvailabilityPage' => $this->snpContentHelper->getSnpContentUrl($pfg_content),
          ];
        }

        $association_list[] = [
          'siret' => $siret,
          'name' => reset($pfg_contents)->get('field_precision_type_eg')->value,
          'isInterfaced' => $this->sosDoctorsIsInterfacedHelper->isSosDoctorsInterfaced($siret),
          'pfgList' => $pfg_list,
          'settingsLink' => $this->structureSettingsHelper->getSosMedecinSettingsUrl($siret, $user),
        ];
      }
    }

    return $association_list;
  }

}
