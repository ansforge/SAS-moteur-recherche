<?php

declare(strict_types = 1);

namespace Drupal\sas_structure\Service;

use Drupal\Core\Database\Connection;

/**
 * This class handles data access and manipulation tasks for interfaced Siret,
 * including update, and verification of siret status.
 */
class SosDoctorsIsInterfacedHelper implements SosDoctorsIsInterfacedHelperInterface {

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
   * Get siret interface.
   *
   * @param string $siret
   *
   * @return array|bool
   */
  public function get(string $siret): bool|array {
    return $this->database->select('sas_siret_interfaced', 's')
      ->fields('s')
      ->condition('siret', $siret)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * Get All siret value from table sas_siret_interfaced.
   *
   * @return array
   */
  public function getAllSiret(): array {
    $result = $this->database->select('sas_siret_interfaced', 's')
      ->fields('s', ['siret'])
      ->execute()
      ->fetchAll();

    return array_column($result, 'siret');
  }

  /**
   * Save a siret as interfaced.
   *
   * @param string $siret
   *
   * @return void
   *
   * @throws \Exception
   */
  public function save(string $siret): void {
    $this->database->insert('sas_siret_interfaced')
      ->fields(['siret' => $siret])
      ->execute();
  }

  /**
   * Check if sos doctors is interfaced.
   *
   * @param string $siret
   *   Siret number.
   *
   * @return bool
   */
  public function isSosDoctorsInterfaced(string $siret): bool {
    $result = $this->get($siret);

    return (bool) $result;
  }

}
