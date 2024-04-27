<?php

namespace Drupal\sas_structure\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\taxonomy\TermInterface;

/**
 * Class SosMedecinHelper.
 *
 * SOS Medecin structure helper.
 *
 * @package Drupal\sas_structure\Service
 */
class SosMedecinHelper implements SosMedecinHelperInterface {

  /**
   * Cache ID of association list.
   */
  const ASSOCIATION_LIST_CACHE_ID = 'sas:sos_medecin:association_list';

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * SosMedecinHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   */
  public function getAssociationList(string $search = ''): array {

    // If no search to filter list, try to get it from cache.
    if (empty($search)) {
      $cache = $this->cache->get(self::ASSOCIATION_LIST_CACHE_ID);
      if ($cache) {
        return $cache->data;
      }
    }

    // Get SOS Médecin eg type term.
    $sos_medecin_term = $this->getSosMedecinEgTypeTerm();
    if (empty($sos_medecin_term)) {
      return [];
    }

    try {
      // Build base entity query.
      $query = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()
        ->condition('type', StructureConstant::SOS_MEDECIN_CONTENT_TYPE)
        ->condition('status', Node::PUBLISHED)
        ->condition('field_eg_type', $sos_medecin_term->id());

      // Add association name filter if search is given.
      if (!empty($search)) {
        $or_cond = $query->orConditionGroup()
          ->condition(StructureConstant::SOS_MEDECIN_ASSOCIATION_NAME_FIELD, $search, 'CONTAINS')
          ->condition('field_identif_siret', $search, 'CONTAINS');
        $query->condition($or_cond);
      }

      $result = $query->execute();
      if (empty($result)) {
        return [];
      }

      /** @var \Drupal\node\NodeInterface[] $nodes */
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple(array_values($result));
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return [];
    }

    if (empty($nodes)) {
      return [];
    }

    $associations = [];

    // Build association list.
    foreach ($nodes as $node) {
      if (
        $node->hasField('field_identif_siret') &&
        $node->hasField(StructureConstant::SOS_MEDECIN_ASSOCIATION_NAME_FIELD) &&
        !$node->get('field_identif_siret')->isEmpty() &&
        !$node->get(StructureConstant::SOS_MEDECIN_ASSOCIATION_NAME_FIELD)->isEmpty()
      ) {
        $associations[$node->get('field_identif_siret')->value] = $node->get(StructureConstant::SOS_MEDECIN_ASSOCIATION_NAME_FIELD)->value;
      }
    }

    // Add to cache only full list without filter.
    if (empty($search)) {
      $this->cache->set(
        self::ASSOCIATION_LIST_CACHE_ID,
        $associations,
        CacheBackendInterface::CACHE_PERMANENT,
        [
          sprintf('taxonomy_term_list:%s',
            StructureConstant::SOS_MEDECIN_VOCABULARY),
        ]
      );
    }

    return $associations;
  }

  /**
   * {@inheritDoc}
   */
  public function getAssociationNameBySiret(string $siret): ?string {
    // Get all association list keyed by siret.
    $associations = $this->getAssociationList();

    if (empty($associations[$siret])) {
      return '';
    }

    return $associations[$siret];
  }

  /**
   * {@inheritDoc}
   */
  public function isSosMedecinAssociation(string $siret): bool {
    // Get all association list keyed by siret.
    $associations = $this->getAssociationList();

    return !empty($associations[$siret]);
  }

  /**
   * {@inheritDoc}
   */
  public function getAssociationPfg(string $siret, bool $load_node = TRUE): ?array {

    // Get SOS Médecin eg type term.
    $sos_medecin_term = $this->getSosMedecinEgTypeTerm();
    if (empty($sos_medecin_term)) {
      return [];
    }

    try {
      $results = $this->entityTypeManager->getStorage('node')->getQuery()->accessCheck()
        ->condition('type', StructureConstant::SOS_MEDECIN_CONTENT_TYPE)
        ->condition('status', Node::PUBLISHED)
        ->condition('field_eg_type', $sos_medecin_term->id())
        ->condition('field_identif_siret', $siret)
        ->execute();

      if (empty($results)) {
        return [];
      }

      if ($load_node) {
        /** @var \Drupal\node\NodeInterface[] $nodes */
        return $this->entityTypeManager->getStorage('node')
          ->loadMultiple(array_values($results));
      }
      return array_values($results);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return [];
    }
  }

  /**
   * Get the eg_type taxonomy term corresponding to "SOS Médecin".
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   Taxonomy term corresponding to SOS Médecin EG type.
   */
  protected function getSosMedecinEgTypeTerm(): ?TermInterface {
    try {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => StructureConstant::SOS_MEDECIN_VOCABULARY,
          'name' => StructureConstant::SOS_MEDECIN_TERM,
        ]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return NULL;
    }

    if (empty($term)) {
      return NULL;
    }

    return reset($term);
  }

  public function getAssociationScheduleIds(string $siret): array {
    $schedule_ids = [];
    $pfgs = $this->getAssociationPfg($siret, FALSE);

    try {
      $schedules = $this->entityTypeManager
        ->getStorage('node')
        ->loadByProperties([
          'type' => SnpConstant::SAS_TIME_SLOTS,
          'field_sas_time_slot_ref' => $pfgs,
        ]);
    }
    catch (\Exception $e) {
      return $schedule_ids;
    }

    foreach ($schedules as $schedule) {
      if (!empty($schedule_id = $schedule->field_sas_time_slot_schedule_id->value)) {
        $schedule_ids[] = $schedule_id;
      }
    }

    return $schedule_ids;
  }

}
