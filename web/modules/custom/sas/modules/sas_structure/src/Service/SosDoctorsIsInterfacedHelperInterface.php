<?php

namespace Drupal\sas_structure\Service;

/**
 * Interface SosIsInterfacedHelperInterface.
 *
 * Interface to implements to manage helpers functions for is siret interfaced.
 *
 * @package Drupal\sas_structure\Service
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousPrefix
 */
interface SosDoctorsIsInterfacedHelperInterface {

  /**
   * Get siret interface.
   *
   * @param string $siret
   *
   * @return array|bool
   */
  public function get(string $siret): bool|array;

  /**
   * Get All siret value from table sas_siret_interfaced.
   *
   * @return array
   */
  public function getAllSiret(): array;

  /**
   * Save a siret as interfaced.
   *
   * @param string $siret
   *
   * @return void
   *
   * @throws \Exception
   */
  public function save(string $siret): void;

  /**
   * Check if sos doctors is interfaced.
   *
   * @param string $siret
   *   Siret number.
   *
   * @return bool
   */
  public function isSosDoctorsInterfaced(string $siret): bool;

}
