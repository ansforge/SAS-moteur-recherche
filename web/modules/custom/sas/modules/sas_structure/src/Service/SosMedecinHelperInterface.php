<?php

namespace Drupal\sas_structure\Service;

/**
 * Interface SosMedecinHelperInterface.
 *
 * Define structure for service related to "SOS Médecin" structures.
 *
 * @package Drupal\sas_structure\Service
 */
interface SosMedecinHelperInterface {

  /**
   * Build and get "SOS Médecin" associations list.
   *
   * @param string $search
   *   If given, filter item by association name containing search.
   *
   * @return array
   *   List of SOS Médecin association corresponding to searched text.
   */
  public function getAssociationList(string $search = ''): array;

  /**
   * Get SOS médecin association name by its siret.
   *
   * @param string $siret
   *   Siret number of SOS Medecin association.
   *
   * @return string|null
   *   SOS Médecin association name.
   */
  public function getAssociationNameBySiret(string $siret): ?string;

  /**
   * Check if siret is one of SOS Médecin association.
   *
   * @param string $siret
   *   Siret number to check.
   *
   * @return bool
   *   True if SOS médecin association, false else.
   */
  public function isSosMedecinAssociation(string $siret): bool;

  /**
   * Get "Point fixe de garde" (PFG) with SOS Médecin association's siret.
   *
   * @param string $siret
   *   Association's siret.
   * @param bool $load_node
   *   If set to TRUE, load node object and return them, else return node ID.
   *
   * @return array|null
   *   List of "Point Fixe de Garde" (PFG) content (Entité Géographique).
   */
  public function getAssociationPfg(string $siret, bool $load_node = TRUE): ?array;

}
