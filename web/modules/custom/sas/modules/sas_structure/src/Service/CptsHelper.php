<?php

namespace Drupal\sas_structure\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\user\UserInterface;

/**
 * Provides helper functions for managing CPTS (Communautés Professionnelles Territoriales de Santé).
 *
 * This service class contains methods for retrieving information
 * about PS associated with specific CPTS based on FINESS numbers.
 */
class CptsHelper implements CptsHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Finess Structeure service.
   *
   * @var \Drupal\sas_structure\Service\FinessStructureHelperInterface
   */
  protected FinessStructureHelperInterface $finessStructureHelper;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * Sas user data helper.
   *
   * @var Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper
   */
  protected SasSnpUserDataHelper $sasSnpUserDataHelper;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * CptsHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\sas_user\Service\SasEffectorHelperInterface $sasEffectorHelper
   * @param FinessStructureHelperInterface $finess_StructureHelper
   * @param \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelper $sasSnpUserDataHelper
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    SasEffectorHelperInterface $sasEffectorHelper,
    FinessStructureHelperInterface $finess_StructureHelper,
    SasSnpUserDataHelper $sasSnpUserDataHelper,
    CacheBackendInterface $cache,
    Connection $database,
    CurrentPathStack $current_path
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->sasEffectorHelper = $sasEffectorHelper;
    $this->finessStructureHelper = $finess_StructureHelper;
    $this->sasSnpUserDataHelper = $sasSnpUserDataHelper;
    $this->cache = $cache;
    $this->database = $database;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritDoc}
   */
  public function getEffectorByCpts(array $finessNumbers): array {
    $cptsDetailsList = [];

    foreach ($finessNumbers as $finessNumber) {
      // Get the CPTS from its FINESS number.
      $cptsNode = $this->finessStructureHelper->getStructureByFiness($finessNumber);
      $cptsTitle = $cptsNode ? $cptsNode->getTitle() : '';

      // Get the RPPS for the given FINESS number from sas_snp_user_data Table.
      $usersData = $this->getUserDataForCpts($finessNumber);

      $effectorsList = [];
      if (!empty($usersData)) {
        $effectorsList = $this->buildEffectorsList($usersData);
      }

      $cptsDetailsList[] = [
        'title' => $cptsTitle,
        'finess' => $finessNumber,
        'effectors' => $effectorsList,
      ];
    }

    return $cptsDetailsList;
  }

  /**
   * Retrieves the user data for PS associated with the specified CPTS.
   *
   * @param string $finessNumber
   *   FINESS number of the CPTS.
   *
   * @return array User data for PS associated with CPTS.
   */
  public function getUserDataForCpts(string $finessNumber): array {
    return $this->sasSnpUserDataHelper->getSettingsBy(
      [
        'structure_finess' => $finessNumber,
        'participation_sas_via' => SnpUserDataConstant::SAS_PARTICIPATION_MY_CPTS,
        'participation_sas' => 1,
      ],
      FALSE,
      TRUE
    );
  }

  /**
   * Builds details for a single effector.
   *
   * @param $usersData
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildEffectorsList($usersData) {
    $nidsToLoad = [];
    $effectorsList = [];

    // Get the nids and ps detailes.
    foreach ($usersData as $userData) {
      $userId = $userData['user_id'][0]['value'];
      $registration_date = $userData['settings_updated'][0]['value'];

      $nids = $this->sasEffectorHelper->GetContentByRppsAdeli($userId, SasUserConstants::PREFIX_ID_RPPS);
      if (!empty($nids)) {
        $firstNid = reset($nids);
        if (!in_array($firstNid, $nidsToLoad)) {
          $nidsToLoad[] = $firstNid;
        }
        $effectorsList[$firstNid] = [
          'registration_date' => date('d/m/Y', $registration_date),
          'rpps' => $userId,
        ];
      }
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nidsToLoad);

    foreach ($effectorsList as $nid => &$effector) {
      if (isset($nodes[$nid]) && $nodes[$nid] instanceof ProfessionnelDeSanteSas) {
        $node = $nodes[$nid];
        $dashboardLink = $this->generateDashboardLink('8' . $node->getNationalId()['id']);
        $effector['name'] = $node->getTitle();
        $specialities = $node->getPosSpecialities('label');
        $effector['speciality'] = implode(', ', $specialities);
        $effector['link_ps'] = $dashboardLink;
      }
    }

    return array_values($effectorsList);
  }

  /**
   * {@inheritDoc}
   */
  public function getNidsFromUserSettings(array $users_data): array {
    $nidsList = [];

    foreach ($users_data as $user_data) {
      $activity_rpps_list = $user_data['cpts_locations'][0]['value'] ?? [];
      $place_nids = $this->sasEffectorHelper->getActivityRppsNids($activity_rpps_list);
      if (!empty($place_nids)) {
        $nidsList = array_merge($nidsList, $place_nids);
      }
    }

    return array_unique($nidsList);
  }

  /**
   * {@inheritDoc}
   */
  public function getCptsListForUser(UserInterface $user): array {
    $cptsList = [];

    if ($user->hasField(StructureConstant::CPTS_USER_FIELD_NAME)
        && !$user->get(StructureConstant::CPTS_USER_FIELD_NAME)->isEmpty()) {
      $finessUserList = array_column($user->get(StructureConstant::CPTS_USER_FIELD_NAME)->getValue(), 'value');
      $cptsList = $this->getEffectorByCpts($finessUserList);
    }

    return $cptsList;
  }

  /**
   * Generate the dashboard link for a PS.
   *
   * @param string $rpps
   *   The RPPS ID of the PS.
   *
   * @return string
   *   The generated dashboard link.
   */
  private function generateDashboardLink(string $rpps) {
    $url = Url::fromRoute('sas_user_dashboard.root')
      ->setRouteParameters(['userId' => $rpps])
      ->setOption('query', [
        'back_url' => $this->currentPath->getPath(),
      ]);

    return $url->toString();
  }

}
