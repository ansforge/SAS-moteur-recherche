<?php

namespace Drupal\sas_snp\Manager;

use Drupal\Core\Database\Connection;
use Drupal\sas_search_index\Service\SasSearchIndexHelperInterface;
use Drupal\sas_snp\Model\SasAvailability;

/**
 * Manager class for SASAvailability objects.
 */
class SasAvailabilityManager {

  /**
   * Drupal database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * SAS search index helper service.
   *
   * @var \Drupal\sas_search_index\Service\SasSearchIndexHelperInterface
   */
  protected SasSearchIndexHelperInterface $sasSearchIndexHelper;

  public function __construct(Connection $database, SasSearchIndexHelperInterface $sasSearchIndexHelper) {
    $this->database = $database;
    $this->sasSearchIndexHelper = $sasSearchIndexHelper;
  }

  /**
   * Inserts a new SASAvailability object into the database.
   *
   * @param SASAvailability $availability
   *   The availability object to insert.
   *
   * @return bool
   *   TRUE on successful insertion, FALSE otherwise.
   */
  public function insert(SASAvailability $availability): bool {

    try {
      $this->database->insert('sas_snp_availability')
        ->fields([
          'nid' => $availability->getNid(),
          'has_snp' => (int) $availability->isHasSnp(),
          'is_interfaced' => (int) $availability->isInterfaced(),
        ])
        ->execute();
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Updates an existing SASAvailability object in the database.
   *
   * @param SASAvailability $availability
   *   The availability object to update.
   *
   * @return bool
   *   TRUE on successful update, FALSE otherwise.
   */
  public function update(SASAvailability $availability): bool {

    try {
      $this->database->update('sas_snp_availability')
        ->fields([
          'has_snp' => (int) $availability->isHasSnp(),
          'is_interfaced' => (int) $availability->isInterfaced(),
        ])
        ->condition('nid', $availability->getNid())
        ->execute();

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Loads a SASAvailability object by its NID.
   *
   * @param int $nid
   *   The NID of the availability to load.
   *
   * @return \Drupal\sas_snp\Model\SasAvailability|null
   *   The loaded SASAvailability object or NULL if not found.
   */
  public function loadByNid($nid): ?SasAvailability {
    try {
      $query = $this->database->select('sas_snp_availability', 'a')
        ->fields('a')
        ->condition('nid', $nid)
        ->range(0, 1);

      $result = $query->execute()->fetchAssoc();
      if ($result) {
        return SasAvailability::create($result['nid'], $result['has_snp'], $result['is_interfaced']);
      }
      return NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Loads multiple SASAvailability objects by their NIDs.
   *
   * @param int[] $nids
   *   An array of NIDs of availabilities to load.
   *
   * @return \Drupal\sas_snp\Model\SasAvailability[]
   *   An associative array of loaded SASAvailability objects keyed by their NID.
   */
  public function loadByNids(array $nids): array {

    $query = $this->database->select('sas_snp_availability', 'a')
      ->fields('a')
      ->condition('nid', $nids, 'IN');

    $results = $query->execute()->fetchAllAssoc('nid');

    $availabilities = [];
    foreach ($results as $result) {
      $availabilities[$result->nid] = SasAvailability::create($result->nid, $result->has_snp, $result->is_interfaced);
    }

    return $availabilities;
  }

  public function updateHasSnpAvailability($entity_id, $available): void {
    if (empty($entity_id)) {
      return;
    }

    $availability = $this->loadByNid($entity_id);

    if (empty($availability)) {
      $availability = SasAvailability::create($entity_id, $available, 0);
      $this->insert($availability);
    }
    else {

      if ($availability->isHasSnp() !== $available) {
        $availability->setHasSnp($available);
        $this->update($availability);
      }
    }
  }

}
