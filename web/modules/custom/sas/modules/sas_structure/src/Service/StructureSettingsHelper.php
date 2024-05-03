<?php

namespace Drupal\sas_structure\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\sas_structure\Entity\SasStructureSettings;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\user\UserInterface;

/**
 * Class StructureSettingsHelper.
 *
 * Provide structure settings helper service.
 *
 * @package Drupal\sas_structure\Service
 */
class StructureSettingsHelper implements StructureSettingsHelperInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Structure Helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelperInterface
   */
  protected StructureHelperInterface $structureHelper;

  /**
   * SOS medecin helper.
   *
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $account;

  /**
   * StructureSettingsHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\sas_structure\Service\StructureHelperInterface $structure_helper
   * @param \Drupal\sas_structure\Service\SosMedecinHelperInterface $sos_medecin_helper
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    StructureHelperInterface $structure_helper,
    SosMedecinHelperInterface $sos_medecin_helper,
    AccountProxyInterface $account
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->structureHelper = $structure_helper;
    $this->sosMedecinHelper = $sos_medecin_helper;
    $this->account = $account;
  }

  /**
   * {@inheritDoc}
   */
  public function getSettingsBy(array $filters, bool $to_array = TRUE): SasStructureSettings|array {
    try {
      $result = $this->entityTypeManager
        ->getStorage('sas_structure_settings')
        ->loadByProperties($filters);
    }
    catch (\Exception $e) {
      return [];
    }

    if (empty($result)) {
      return [];
    }

    return $to_array ? reset($result)->toArray() : reset($result);
  }

  /**
   * {@inheritDoc}
   */
  public function getStructureSettingsLink(NodeInterface $node, UserInterface $user): ?array {

    // Only CDS can manage settings.
    if (!$this->structureHelper->isCds($node)) {
      return NULL;
    }

    // Ensure to have finess id.
    if (!$node->hasField('field_identifiant_finess') || $node->get('field_identifiant_finess')->isEmpty()) {
      return NULL;
    }

    // Build settings edit link.
    return Link::createFromRoute('Editer les paramètres', 'entity.sas_structure_settings.edit',
      [
        'node' => $node->id(),
      ],
      [
        'attributes' => [
          'class' => 'use-ajax btn-highlight js-btn-open-modal-sas',
          'data-dialog-type' => 'modal',
          'data-dialog-options' => '{"dialogClass": "modal-sas"}',
        ],
        'query' => [
          'user_id' => $user->id(),
        ],
      ])->toRenderable();
  }

  /**
   * {@inheritDoc}
   */
  public function getSosMedecinAssociationSettingsLink(string $siret, UserInterface $user): ?array {

    // Check if siret correspond to a SOS Médecin association.
    if (!$this->sosMedecinHelper->isSosMedecinAssociation($siret)) {
      return NULL;
    }

    // Build settings edit link.
    return Link::createFromRoute('Editer les paramètres', 'entity.sas_structure_settings.sos_medecin.edit',
      [
        'siret' => $siret,
      ],
      [
        'attributes' => [
          'class' => 'use-ajax btn-highlight js-btn-open-modal-sas',
          'data-dialog-type' => 'modal',
          'data-dialog-options' => '{"dialogClass": "modal-sas"}',
        ],
        'query' => [
          'user_id' => $user->id(),
        ],
      ])->toRenderable();
  }

  /**
   * {@inheritDoc}
   */
  public function getSosMedecinSettingsUrl(string $siret, UserInterface $user): ?string {

    // Check if siret correspond to a SOS Médecin association.
    if (!$this->sosMedecinHelper->isSosMedecinAssociation($siret)) {
      return NULL;
    }

    // Build settings edit link.
    return Link::createFromRoute('Editer les paramètres', 'entity.sas_structure_settings.sos_medecin.edit',
      [
        'siret' => $siret,
      ],
      [
        'query' => [
          'user_id' => $user->id(),
        ],
      ])->getUrl()->toString();
  }

  /**
   * {@inheritDoc}
   */
  public function checkSettingsUpdateAccess(NodeInterface $structure_node): bool {
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

    // If no user or attached structure data found.
    if (
      empty($current_user) ||
      !$current_user->hasField('field_sas_attach_structures') ||
      $current_user->get('field_sas_attach_structures')->isEmpty()
    ) {
      return FALSE;
    }

    // Check if given structure node is part of attached structure for current user.
    $attached_structures = $current_user->get('field_sas_attach_structures')->getValue();
    return array_search($structure_node->id(), array_column($attached_structures, 'target_id')) !== FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function checkSosMedecinSettingsUpdateAccess(string $siret): bool {
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

    if (!$this->sosMedecinHelper->isSosMedecinAssociation($siret)) {
      return FALSE;
    }

    // If no user or attached structure data found.
    if (
      empty($current_user) ||
      !$current_user->hasField(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME) ||
      $current_user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->isEmpty()
    ) {
      return FALSE;
    }

    // Check if given structure node is part of attached structure for current user.
    $siret_list = $current_user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->getValue();
    return array_search($siret, array_column($siret_list, 'value')) !== FALSE;
  }

}
