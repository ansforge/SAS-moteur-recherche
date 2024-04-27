<?php

namespace Drupal\sas_structure\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\sas_structure\Enum\StructureConstant;

/**
 * Class FinessStructureHelper.
 *
 * Provides finess structure helper methods.
 *
 * @package Drupal\sas_structure\Service
 */
class FinessStructureHelper implements FinessStructureHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * @var \Drupal\sas_structure\Service\StructureHelperInterface
   */
  protected StructureHelperInterface $structureHelper;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    StructureHelperInterface $structure_helper
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->structureHelper = $structure_helper;
  }

  /**
   * {@inheritDoc}
   */
  public function searchStructures(string $type, string $text): array {
    $query = $this->database->select(table: 'node_field_data', alias: 'n');

    $query->join(
      table: 'node__field_identifiant_finess',
      alias: 'fif',
      condition: 'fif.entity_id = n.nid'
    );
    $query->leftJoin(
      table: 'node__field_establishment_type',
      alias: 'et',
      condition: 'n.nid=et.entity_id'
    );
    $query->leftJoin(
      table: 'node__field_type_de_service_de_sante',
      alias: 'ss',
      condition: 'n.nid=ss.entity_id'
    );
    $query->leftJoin(
      table: 'node__field_finess_establishment_type',
      alias: 'fet',
      condition: 'n.nid=fet.entity_id'
    );

    $structureTermIds = $this->structureHelper->getStructureTypeTermIds($type);

    if (!empty($structureTermIds)) {
      $db_or = $query->orConditionGroup();
      $db_or->condition('et.field_establishment_type_target_id', $structureTermIds, 'IN');
      $db_or->condition('ss.field_type_de_service_de_sante_target_id', $structureTermIds, 'IN');
      $db_or->condition('fet.field_finess_establishment_type_target_id', $structureTermIds, 'IN');
      $query->condition($db_or);
    }

    $query->condition(
      field: 'n.type',
      value: StructureConstant::STRUCTURE_CONTENT_TYPES,
      operator: 'IN'
    );

    if ($type !== StructureConstant::STRUCTURE_TYPE_CPTS) {
      $query->condition(field: 'n.status', value: NodeInterface::PUBLISHED);
    }

    $query->condition(field: 'n.title', value: '%' . $text . '%', operator: 'LIKE');
    $query->fields(table_alias: 'n', fields: ['nid', 'title']);
    $query->addField(
      table_alias: 'fif',
      field: 'field_identifiant_finess_value',
      alias: 'finess'
    );
    $query->range(start: 0, length: 10);
    $query->orderBy(field: 'nid');

    return $query->execute()->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);
  }

  /**
   * Get Structure by finess ID.
   *
   * @param string $num_finess
   *
   * @return \Drupal\node\Entity\Node|null
   */
  public function getStructureByFiness(string $num_finess): ?Node {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()
      ->condition('field_identifiant_finess', $num_finess)
      ->range(0, 1);
    $nids = $query->execute();
    if (!empty($nids)) {
      $node_id = reset($nids);
      return $this->entityTypeManager->getStorage('node')->load($node_id);
    }
    return NULL;
  }

}
