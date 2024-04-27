<?php

declare(strict_types = 1);

namespace Drupal\sas_snp\Service;

use Drupal\Core\Database\Connection;

/**
 * This class handles data access and manipulation tasks for interfaced RPPS,
 * including update, and verification of RPPS status.
 */
class InterfacedHelper implements InterfacedHelperInterface {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public function get(string $idNat): bool|array {
    return $this->database->select('sas_is_interfaced', 's')
      ->fields('s')
      ->condition('rpps', $idNat)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * Get All rpp value from table.
   *
   * @return array
   */
  public function getAllRpps(): array {
    $result = $this->database->select('sas_is_interfaced', 's')
      ->fields('s', ['rpps'])
      ->execute()
      ->fetchAll();

    return array_column($result, 'rpps');
  }

  /**
   * {@inheritDoc}
   */
  public function save(string $idNat): void {
    $this->database->insert('sas_is_interfaced')
      ->fields(['rpps' => $idNat])
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  public function isInterfaced($idNat): bool {
    $result = $this->get($idNat);

    return (bool) $result;
  }

}
