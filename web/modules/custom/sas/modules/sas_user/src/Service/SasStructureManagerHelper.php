<?php

declare(strict_types = 1);

namespace Drupal\sas_user\Service;

use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Service\CptsHelper;
use Drupal\user\UserInterface;

/**
 * Class SasStructureManagerHelper.
 *
 * Specific helper for Structure manager user account.
 *
 * @package Drupal\sas_user\Service
 */
class SasStructureManagerHelper implements SasStructureManagerHelperInterface {

  /**
   * @var \Drupal\sas_structure\Service\CptsHelper
   */
  protected CptsHelper $cptsHelper;

  /**
   * @param \Drupal\sas_structure\Service\CptsHelper $cpts_helper
   *   CPTS helper.
   */
  public function __construct(
    CptsHelper $cpts_helper
  ) {
    $this->cptsHelper = $cpts_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function getCptsPlaceNids(UserInterface $user): array {
    $nids = [];

    if ($user->hasField(StructureConstant::CPTS_USER_FIELD_NAME) &&
      !$user->get(StructureConstant::CPTS_USER_FIELD_NAME)->isEmpty()) {
      $finess_list = array_column($user->get(StructureConstant::CPTS_USER_FIELD_NAME)->getValue(), 'value');

      foreach ($finess_list as $finess) {
        $usersData = $this->cptsHelper->getUserDataForCpts($finess);
        $nidsList = $this->cptsHelper->getNidsFromUserSettings($usersData);
        $nids = array_merge($nids, $nidsList);
      }

    }

    return array_unique($nids);
  }

  /**
   * {@inheritDoc}
   */
  public function isStructureManager(UserInterface $user, string $structure): bool {
    // Check if the user has the structure.
    $hasStructure = $user->hasField($structure) && !$user->get($structure)->isEmpty();

    // Check if the user has the 'structure manager' role.
    $isStructureManagerRole = in_array(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE, $user->getRoles());

    return $hasStructure && $isStructureManagerRole;
  }

}
