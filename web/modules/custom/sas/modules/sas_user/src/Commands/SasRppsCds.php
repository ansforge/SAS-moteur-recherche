<?php

namespace Drupal\sas_user\Commands;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Connection;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_directory_pages\Entity\Feature\SasSnpHelperTrait;
use Drupal\sas_export\SasCsvFileInterface;
use Drupal\sas_structure\Service\StructureHelperInterface;
use Drush\Commands\DrushCommands;

/**
 * Class SasRppsCds.
 *
 * Drush command to list rpps with 100% location of activity is a CDS.
 *
 * @package Drupal\sas_user\Commands
 */
class SasRppsCds extends DrushCommands {

  use SasSnpHelperTrait;

  /**
   * Structure Helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelperInterface
   */
  protected StructureHelperInterface $structureHelper;

  /**
   * @var \Drupal\sas_export\SasCsvFileInterface
   */
  protected SasCsvFileInterface $csvFile;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The IsInterfacedCommands constructor.
   */
  public function __construct(
    StructureHelperInterface $structure_helper,
    SasCsvFileInterface $csv_file,
    SasCoreServiceInterface $sasCoreService,
    Connection $database
  ) {
    parent::__construct();
    $this->structureHelper = $structure_helper;
    $this->csvFile = $csv_file;
    $this->sasCoreService = $sasCoreService;
    $this->database = $database;
  }

  /**
   * Lists the RPPS with 100% location of activity is a CDS.
   * and add to a csv files.
   *
   * @command sas_user:list_sas_user
   *
   * @usage sas_user:list_sas_user
   *   lists the RPPS with 100% location of activity is a CDS.
   */
  public function listSasRpps() {
    if (!$this->sasCoreService->isSasContext()) {
      throw new PluginException('Cette commande doit être lancée dans le contexte SAS.');
    }

    $result = $this->queryDatabaseForUserAndFinessData();

    $finalUserList = [];

    foreach ($result as $row) {
      $user_id = $row->user_id;
      $finess = $row->finess_value;

      if (!isset($finalUserList[$user_id])) {
        $finalUserList[$user_id] = [];
      }
      $finalUserList[$user_id][] = $finess;
    }

    $csvData = [];
    foreach ($finalUserList as $user_id => $finess_list) {
      if ($this->allFinessAreCds($finess_list)) {
        $csvData[] = ['user_id' => $user_id, 'finess_list' => implode(', ', $finess_list)];
      }
    }

    $this->csvFile->buildCsvFile($csvData, 'export_rpps');
    $this->writeln(sprintf('%d user(s) and associated finess exported to CSV file. See the CSV file at %s', count($csvData), $this->csvFile->getFilePath()));
  }

  private function allFinessAreCds(array $finess_list): bool {
    foreach ($finess_list as $finess) {
      if (!empty($finess)) {
        $node = $this->getContentByFiness($finess);
        if (!$node || !$this->structureHelper->isCds($node)) {
          return FALSE;
        }

      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

  private function queryDatabaseForUserAndFinessData() {
    $query = $this->database->select('sas_snp_user_data', 'snp')
      ->fields('snp', ['user_id'])
      ->condition('snp.participation_sas', 1, '=');

    $query->join('node__field_identifiant_rpps', 'rpps', 'snp.user_id = rpps.field_identifiant_rpps_value');
    $query->addField('rpps', 'entity_id');
    $query->isNotNull('rpps.entity_id');

    $query->leftJoin('node__field_identifiant_str_finess', 'finess', 'rpps.entity_id = finess.entity_id');
    $query->addField('finess', 'field_identifiant_str_finess_value', 'finess_value');

    return $query->execute();
  }

}
