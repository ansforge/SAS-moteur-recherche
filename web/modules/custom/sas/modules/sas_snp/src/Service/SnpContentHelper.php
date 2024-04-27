<?php

namespace Drupal\sas_snp\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Enum\SnpContentConstant;

/**
 * Class SnpContentHelper.
 *
 * Helper providing data on SNP content.
 *
 * @package Drupal\sas_snp\Service
 */
class SnpContentHelper implements SnpContentHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a new Controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $accountProxy,
    CurrentPathStack $current_path,
    Connection $database
  ) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->currentUser = $accountProxy;
    $this->currentPath = $current_path;
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public function isSupportSasSnpEntity(NodeInterface $node): bool {
    $bundle = $node->bundle();
    $allowed_terms_by_type = SnpContentConstant::getTermNamesByContentTypes();
    // If content type is not allowed for SNP.
    if (empty($bundle) ||
      !in_array($bundle, array_merge(
        array_keys($allowed_terms_by_type),
        array_values(SnpContentConstant::SAS_ALLOWED_ENTITY)
      ))) {
      return FALSE;
    }

    // If content type is SNP container (SAS - Plage Horaire)
    if (in_array($bundle, SnpContentConstant::SAS_ALLOWED_ENTITY)) {
      return TRUE;
    }

    // If content does not contains allowed taxonomy field.
    $field = SnpContentConstant::ALLOWED_ENTITY_FIELDS_IDS[$bundle];
    if (!$node->hasField($field)) {
      return FALSE;
    }

    // If content as no data for allowed field or bad type.
    if ((!array_key_exists($bundle, SnpContentConstant::ALLOWED_ENTITY_FIELDS_IDS)) || $node->get($field)->isEmpty()) {
      return FALSE;
    }

    /** @var \Drupal\taxonomy\TermInterface[] $taxoEntity */
    $taxoEntity = $node->get($field)->referencedEntities();
    if (empty($taxoEntity)) {
      return FALSE;
    }

    // Check if term match one of allowed terms.
    if (in_array(reset($taxoEntity)->getName(), $allowed_terms_by_type[$bundle])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getChild(NodeInterface $node): NodeInterface|FALSE {

    $result = $this->nodeStorage->loadByProperties(['field_sas_time_slot_ref' => $node->id()]);

    if (!empty($result) && is_array($result)) {
      // If more than one, return only first result.
      return reset($result);
    }

    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function getParent(NodeInterface $node): NodeInterface|FALSE {

    if (in_array($node->bundle(), SnpContentConstant::SAS_ALLOWED_ENTITY)) {
      $ref_node = $node->get(SnpContentConstant::SAS_SNP_REF_FIELD[$node->bundle()])->referencedEntities();
      if (!empty($ref_node) && is_array($ref_node)) {
        // If more than one, return only first result.
        return reset($ref_node);
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getSnpContentUrl(NodeInterface $node): ?string {
    if ($this->isSupportSasSnpEntity($node) && $this->hasSnpContentAccess($node)) {
      if ($node instanceof ProfessionnelDeSanteSas) {
        $id_nat = sprintf(
          '%s%s',
          $node->getNationalId()['prefix'],
          $node->getNationalId()['id']
        );
      }
      $user_id = $this->currentUser->id();
      $routDelegataire = Url::fromRoute('sas_user_dashboard.delegataire', ['user' => $user_id])
        ->toString(TRUE)->getGeneratedUrl();

      return $node->toUrl(
        rel: 'sas-snp-availability',
        options: [
          'node' => $node->id(),
          'query' => [
            'back_url' => Url::fromRoute('sas_user_dashboard.root',
              [
                'userId' => !empty($id_nat) ? $id_nat : $user_id,
                in_array(
                  SnpConstant::SAS_DELEGATAIRE,
                  $this->currentUser->getRoles())
                  ? 'back_url'
                  : '' => in_array(
                  SnpConstant::SAS_DELEGATAIRE,
                  $this->currentUser->getRoles())
                  ? $routDelegataire
                  : '',
              ]
            )->toString(TRUE)->getGeneratedUrl(),
          ],
        ]
      )->toString(TRUE)->getGeneratedUrl();
    }

    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function hasSnpContentAccess(NodeInterface $node): bool {

    if (!$this->isSupportSasSnpEntity($node)) {
      return FALSE;
    }

    if ($node->bundle() === SnpConstant::SAS_TIME_SLOTS) {
      $access = $node->access('view', $this->currentUser);
    }
    else {
      $access = FALSE;
      $snp_content = $this->getChild($node);

      if (!empty($snp_content)) {
        $access = $snp_content->access('view', $this->currentUser);
      }
      else {
        $snp_content = $this->nodeStorage->create([
          'type' => SnpConstant::SAS_TIME_SLOTS,
          'title' => sprintf('sas_snp_%s', $node->id()),
          'field_sas_time_slot_ref' => $node->id(),
          'uid' => 0,
          'moderation_state' => 'published',
        ]);
        /*
         * Call access control with view operation because access control is
         * the same as other method.
         */
        $access = $snp_content->access('view', $this->currentUser);
      }
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlotRefByScheduleId(int $schedule_id): array|FALSE {

    $query = $this->database->select('node__field_sas_time_slot_schedule_id', 'schedule_id');
    $query->addField('schedule_id', 'field_sas_time_slot_schedule_id_value', 'schedule_id');
    $query->addField('timeslot', 'field_sas_time_slot_ref_target_id', 'slot_ref_target_id');
    $query->join('node__field_sas_time_slot_ref', 'timeslot', 'timeslot.entity_id = schedule_id.entity_id');
    $query->condition('schedule_id.field_sas_time_slot_schedule_id_value', $schedule_id);
    $slotRef = $query->execute()->fetchAll();

    return $slotRef ?? FALSE;

  }

}
