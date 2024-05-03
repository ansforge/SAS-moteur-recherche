<?php

namespace Drupal\sas_structure\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
      value: $type === StructureConstant::STRUCTURE_TYPE_CPTS ?
        StructureConstant::CPTS_CONTENT_TYPE : StructureConstant::STRUCTURE_CONTENT_TYPES,
      operator: 'IN'
    );

    if ($type !== StructureConstant::STRUCTURE_TYPE_CPTS) {
      $query->condition(field: 'n.status', value: NodeInterface::PUBLISHED);
    }

    $db_or = $query->orConditionGroup();
    $db_or->condition(field: 'n.title', value: '%' . $text . '%', operator: 'LIKE');
    $db_or->condition(field: 'fif.field_identifiant_finess_value', value: '%' . $text . '%', operator: 'LIKE');
    $query->condition($db_or);
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
  public function getStructureByFiness(
    string $num_finess,
    string $structure_content_type = NULL
  ): ?EntityInterface {

    try {
      $node_storage = $this->entityTypeManager->getStorage('node');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return NULL;
    }

    $query = $node_storage->getQuery()->accessCheck()
      ->condition('field_identifiant_finess', $num_finess)
      ->range(0, 1);

    if (!empty($structure_content_type)) {
      $query->condition('type', $structure_content_type);
    }

    $nids = $query->execute();

    if (!empty($nids)) {
      return $node_storage->load(reset($nids));
    }

    return NULL;
  }

}
