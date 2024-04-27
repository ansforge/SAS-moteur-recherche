<?php

namespace Drupal\sas_snp\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_snp\Service\SnpUnavailabilityHelper;
use Drush\Commands\DrushCommands;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Drush commands for SasUpdatedSnpCommands class.
 */
class SasUpdateSnpCommands extends DrushCommands {

  /**
   * Constructor for SasUpdateSnpCommands class.
   */
  public function __construct(
    protected Connection $database,
    protected SasCoreServiceInterface $sasCoreService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SnpUnavailabilityHelper $sasSnpUnavailabilityHelper
  ) {
    parent::__construct();
  }

  /**
   * Drush command to process items.
   *
   * @command sas-snp-update
   * @aliases sas-snp-update
   * @usage drush sas-snp-update
   * @usage drush sas-snp-update
   */
  public function processItems() {
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Cette commande doit être lancé dans le context SAS.');
    }

    $query = $this->database->select('node__field_sas_time_slot_schedule_id', 's');
    $query->addField('r', 'field_sas_time_slot_ref_target_id', 'entity_id');
    $query->addField('s', 'field_sas_time_slot_schedule_id_value', 'schedule_id');
    $query->addField('snp', 'participation_sas_via', 'participation_sas_via');
    $query->join('node__field_sas_time_slot_ref', 'r', 's.entity_id = r.entity_id');
    $query->leftjoin('node__field_identifiant_rpps', 'rpps', 'rpps.entity_id = r.field_sas_time_slot_ref_target_id');
    $query->leftjoin('sas_snp_user_data', 'snp', 'snp.user_id = rpps.field_identifiant_rpps_value');
    $schedules = $query->execute()->fetchAllAssoc('entity_id');

    if (empty($schedules)) {
      return;
    }

    $max_items = 100;
    $operations = [];
    $result = $this->sasSnpUnavailabilityHelper->getPsNidsWithUnavailabilityInNextThreeDays();
    $idsPs = !empty($result) ? $result : [];

    $slices = array_chunk($schedules, $max_items, TRUE);
    foreach ($slices as $slice) {
      $operations[] = [
        'Drupal\sas_snp\Batch\UpdateSnpBatch::updateSnp',
        [$slice, $idsPs],
      ];
    }

    $batch = [
      'title' => 'Mise à jour du champ hasSnp',
      'operations' => $operations,
      'finished' => 'Drupal\sas_snp\Batch\UpdateSnpBatch::finished',
    ];

    batch_set($batch);
    if (PHP_SAPI !== 'cli') {
      $redirectResponse = batch_process(Url::fromRoute('entity.simple_cron_job.collection'));
      $redirectResponse->send();
    }
    else {
      $batch['progressive'] = FALSE;
      drush_backend_batch_process();
    }
  }

  /**
   * Drush command to update SNP slots and schedules timezone.
   *
   * @command sas-snp-update-tz
   * @aliases sas-snp-update-tz
   * @usage drush sas-snp-update-tz
   */
  public function updateSnpTimezone() {
    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Cette commande doit être lancé dans le context SAS.');
    }

    $query = $this->database->select('node__field_sas_time_slot_schedule_id', 's');
    $query->addField('s', 'field_sas_time_slot_schedule_id_value', 'schedule_id');
    $query->addField('ref', 'field_sas_time_slot_ref_target_id', 'node_id');
    $query->join('node__field_sas_time_slot_ref', 'ref', 's.entity_id = ref.entity_id');
    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($results)) {
      return;
    }

    $operations = [];
    $chunks = array_chunk($results, 50);
    foreach ($chunks as $chunk) {
      $operations[] = [
        'Drupal\sas_snp\Batch\UpdateSnpTimezoneBatch::updateSnpSlots',
        [$chunk],
      ];
    }

    $batch = [
      'title' => 'Mise à jour des fuseaux horaires des calendriers SNP',
      'operations' => $operations,
      'finished' => 'Drupal\sas_snp\Batch\UpdateSnpTimezoneBatch::finished',
    ];

    batch_set($batch);
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();
  }

  /**
   * Drush command that read a csv file to update "SAS - Interfacé éditeur"
   * field inside "Professionnel de santé" bundle.
   *
   * @command sas_interface_missing_node
   * @usage sas_interface_missing_node
   */
  public function interfaceMissingPeople() {

    if (!$this->sasCoreService->isSasContext()) {
      throw new AccessDeniedHttpException('Cette commande doit être lancé dans le context SAS.');
    }

    $file = fopen(__DIR__ . '/../../assets/csv/nodes_to_interface.csv', 'r');
    $node_ids = [];
    while ($row = fgetcsv($file)) {
      $node_ids[] = $row[0];
    }

    fclose($file);

    if (empty($node_ids)) {
      $this->writeln('no node found from list');
      return;
    }

    $entity = $this->entityTypeManager->getStorage('node');

    $nodes = $entity->loadMultiple($node_ids);

    if (empty($nodes)) {
      $this->writeln('no corresponding node found from database');
      return;
    }
    foreach ($nodes as $node) {
      $node->set('field_is_interfaced', TRUE);
      $node->save();
    }

    $operations = [];
    $operations[] = [
      'Drupal\sas_snp\Batch\UpdateSnpInterfacedBatch::updateInterfacedSnp',
      [$nodes],
    ];

    $batch = [
      'title' => 'Mise à jour du champ hasSnp',
      'operations' => $operations,
      'finished' => 'Drupal\sas_snp\Batch\UpdateSnpInterfacedBatch::finished',
    ];

    batch_set($batch);
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    drush_backend_batch_process();

  }

}
