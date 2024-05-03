<?php

namespace Drupal\sas_structure\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\sas_structure\Enum\StructureConstant;

/**
 * Class StructureHelper.
 *
 * Service provide helpers on structure.
 *
 * @package Drupal\sas_structure\Service
 */
class StructureHelper implements StructureHelperInterface {

  /**
   * Drupal database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $termStorage;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * StructureHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache manager.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entityTypeManager,
    CacheBackendInterface $cache,
    SosMedecinHelperInterface $sosMedecinHelper
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache;
    $this->sosMedecinHelper = $sosMedecinHelper;
  }

  /**
   * {@inheritDoc}
   */
  public function isMsp(NodeInterface $node): bool {
    // Try backend cache.
    $cache_key = sprintf('sas:is_msp:%d', $node->id());
    if ($cached = $this->cache->get($cache_key)) {
      // Cache clear on node & term updates.
      return $cached->data;
    }

    if (!in_array($node->bundle(), StructureConstant::STRUCTURE_MSP_CONTENT_TYPES)) {
      return FALSE;
    }

    // No cache: get a fresh value.
    $msp_term_ids = $this->getStructureTypeTermIds(StructureConstant::STRUCTURE_TYPE_MSP);

    if (empty($msp_term_ids)) {
      return FALSE;
    }

    $is_msp = $this->checkStructureType($node, StructureConstant::STRUCTURE_MSP_FIELDS, $msp_term_ids);

    $this->cache->set($cache_key, $is_msp, Cache::PERMANENT, ['sas_is_msp']);

    return $is_msp;
  }

  /**
   * {@inheritDoc}
   */
  public function isCds(NodeInterface $node): bool {

    // Try backend cache.
    $cache_key = sprintf('sas:is_cds:%d', $node->id());
    if ($cached = $this->cache->get($cache_key)) {
      // Cache clear on node & term updates.
      return $cached->data;
    }

    if (!in_array($node->bundle(), StructureConstant::STRUCTURE_CDS_CONTENT_TYPES)) {
      return FALSE;
    }

    // No cache: get a fresh value.
    $cds_term_ids = $this->getStructureTypeTermIds(StructureConstant::STRUCTURE_TYPE_CDS);

    if (empty($cds_term_ids)) {
      return FALSE;
    }

    $is_cds = $this->checkStructureType($node, StructureConstant::STRUCTURE_CDS_FIELDS, $cds_term_ids);

    $this->cache->set($cache_key, $is_cds, Cache::PERMANENT, ['sas_is_cds']);

    return $is_cds;
  }

  /**
   * {@inheritDoc}
   */
  public function isCpts(NodeInterface $node): bool {

    // Try backend cache.
    $cache_key = sprintf('sas:is_cpts:%d', $node->id());
    if ($cached = $this->cache->get($cache_key)) {
      // Cache clear on node & term updates.
      return $cached->data;
    }

    $is_cpts = FALSE;
    if (in_array($node->bundle(), StructureConstant::CPTS_CONTENT_TYPE)) {
      // No cache: get a fresh value.
      $cpts_term_ids = $this->getStructureTypeTermIds(StructureConstant::STRUCTURE_TYPE_CPTS);
      if (!empty($cpts_term_ids)) {
        $is_cpts = $this->checkStructureType($node, StructureConstant::STRUCTURE_CPTS_FIELDS, $cpts_term_ids, FALSE);
      }
    }

    $this->cache->set($cache_key, $is_cpts, Cache::PERMANENT, ['sas_is_cpts']);
    return $is_cpts;
  }

  /**
   * Check if node match to wanted structure type (cds or msp).
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to check.
   * @param array $structure_fields
   *   Fields to check.
   * @param array $term_ids
   *   Terms ids to check (corresponding to types).
   *
   * @return bool
   *   True if current node match types, false else.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function checkStructureType(NodeInterface $node, array $structure_fields, array $term_ids, bool $only_published = TRUE): bool {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()
      ->condition('nid', $node->id());
    if ($only_published) {
      $query->condition('status', 1);
    }
    $or_condition = $query->orConditionGroup();
    foreach ($structure_fields as $field_name) {
      $or_condition->condition($field_name, $term_ids, 'IN');
    }
    $query->condition($or_condition);

    $count = $query->count()->execute();

    return !empty($count);
  }

  /**
   * {@inheritDoc}
   */
  public function getStructureTypeTermIds(string $type): array {

    // Try backend cache.
    $cache_key = sprintf('sas:structure_type:%s:term_ids', $type);
    if ($cached = $this->cache->get($cache_key)) {
      // Cache clear on node & term updates.
      $tids = $cached->data;
    }
    else {
      try {
        $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()->accessCheck()
          ->condition('vid', StructureConstant::STRUCTURE_TYPE_TAXONOMIES, 'IN');
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        return [];
      }

      switch ($type) {
        case StructureConstant::STRUCTURE_TYPE_MSP:
          $query->condition('name', StructureConstant::STRUCTURE_MSP_TERMS, 'IN');
          break;

        case StructureConstant::STRUCTURE_TYPE_CDS:
          $query->condition('name', StructureConstant::STRUCTURE_CDS_TERMS, 'IN');
          break;

        case StructureConstant::STRUCTURE_TYPE_CPTS:
          $query->condition('name', StructureConstant::STRUCTURE_CPTS_TERM, 'IN');
          break;

        default:
          return [];
      }

      $tids = $query->execute();

      if (!empty($tids)) {
        $this->cache->set($cache_key, $tids,
          CacheBackendInterface::CACHE_PERMANENT, ['sas_structure_type_tids']);
      }
    }

    return $tids;
  }

  /**
   * {@inheritDoc}
   */
  public function getStructureDataByFiness(string $finess, string $structure_content_type = NULL) {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->leftJoin('node__field_identifiant_finess', 'fif', 'fif.entity_id = n.nid');
    $query->leftJoin('node__field_telephone_fixe', 'ftf', 'ftf.entity_id = n.nid');
    $query->addField('fif', 'field_identifiant_finess_value');
    $query->addField('ftf', 'field_telephone_fixe_value');
    $query->condition('fif.field_identifiant_finess_value', $finess);

    if (!empty($structure_content_type)) {
      $query->condition('type', $structure_content_type);
    }
    $result = $query->execute()->fetchAll();

    return !empty($result) ? reset($result) : NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getStructureBasicInfo(string $id_structure, string $id): mixed {
    $structure = [
      'id' => $id,
      'id_structure' => $id_structure,
    ];

    switch ($id_structure) {
      case StructureConstant::STRUCTURE_TYPE_SOS_MEDECIN:
        $name = $this->sosMedecinHelper->getAssociationNameBySiret($id);

        if (empty($name)) {
          return [];
        }

        $structure['title'] = $name;
        break;

      case StructureConstant::STRUCTURE_TYPE_CPTS:
        $cpts = $this->getDataStructure(
          $id,
          StructureConstant::CONTENT_TYPE_HEALTH_INSTITUTION
        );
        $structure['title'] = $cpts['title'];
        $structure['nid'] = $cpts['nid'];
        break;

      case StructureConstant::STRUCTURE_TYPE_MSP:
        $msp = $this->getDataStructure(
          $id
        );
        $structure['title'] = $msp['title'];
        $structure['nid'] = $msp['nid'];
        break;

      default:
        $structure = [];
    }

    return $structure;
  }

  /**
   * {@inheritDoc}
   */
  public function getDataStructure(string $id, string $content_type = NULL): array {
    $data = $this->getStructureDataByFiness(
      $id,
      $content_type
    );

    if (empty($data)) {
      return [];
    }

    return [
      'title' => $data->title,
      'nid' => $data->nid,
    ];
  }

}
