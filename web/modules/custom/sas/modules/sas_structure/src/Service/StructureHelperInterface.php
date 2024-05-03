<?php

namespace Drupal\sas_structure\Service;

use Drupal\node\NodeInterface;

/**
 * Interface StructureHelperInterface.
 *
 * Define structure helper skeleton.
 *
 * @package Drupal\sas_structure\Service
 */
interface StructureHelperInterface {

  /**
   * Check if given node is a MSP.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check.
   *
   * @return bool
   *   Return True if it is a MSP, false else.
   */
  public function isMsp(NodeInterface $node): bool;

  /**
   * Check if given node is a CDS.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check.
   *
   * @return bool
   *   Return True if it is a CDS, false else.
   */
  public function isCds(NodeInterface $node): bool;

  /**
   * Check if given node is a CPTS.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check.
   *
   * @return bool
   *   Return True if it is a CPTS, false else.
   */
  public function isCpts(NodeInterface $node): bool;

  /**
   * Get structure data by its FINESS number.
   *
   * @param string $finess
   *   Structure FINESS number.
   * @param string|null $structure_content_type
   *   Content type to filter structure search.
   */
  public function getStructureDataByFiness(string $finess, string $structure_content_type = NULL);

  /**
   * Get term id for a given structure type.
   *
   * @param string $type
   *   Structure type.
   *
   * @return array
   *   List of term ids.
   */
  public function getStructureTypeTermIds(string $type): array;

  /**
   * Get structure data.
   *
   * @param string $id_structure
   * @param string $id
   *
   * @return mixed
   */
  public function getStructureBasicInfo(string $id_structure, string $id): mixed;

  /**
   * Get data cpts/msp.
   *
   * @param string $id
   *   Numéro finess.
   * @param string $content_type
   *   Content type.
   *
   * @return array
   */
  public function getDataStructure(string $id, string $content_type): array;

}
