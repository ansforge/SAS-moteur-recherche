<?php

namespace Drupal\sas_snp\Service;

/**
 * Interface InterfacedHelperInterface.
 *
 * Interface to implements to manage helpers functions for is interfaced.
 *
 * @package Drupal\sas_snp\Service
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousPrefix
 */
interface InterfacedHelperInterface {

  /**
   * Get an interface National ID.
   *
   * @param string $idNat
   *
   * @return array|bool
   */
  public function get(string $idNat): bool|array;

  /**
   * Get All rpps value from table sas_is_interfaced.
   *
   * @return array
   */
  public function getAllRpps(): array;

  /**
   * Save a national ID (rpps/adeli) as interfaced.
   *
   * @param string $idNat
   *
   * @return void
   *
   * @throws \Exception
   */
  public function save(string $idNat): void;

  /**
   * Check if national ID is interfaced.
   *
   * @param string $idNat
   *   National ID (rpps or adeli) WITH the prefix.
   *
   * @return bool
   */
  public function isInterfaced(string $idNat): bool;

}
