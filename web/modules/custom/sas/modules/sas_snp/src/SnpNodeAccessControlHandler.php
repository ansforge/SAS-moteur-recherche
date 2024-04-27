<?php

namespace Drupal\sas_snp;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeAccessControlHandler;
use Drupal\node\NodeInterface;
use Drupal\sas_directory_pages\Entity\ProfessionnelDeSanteSas;
use Drupal\sas_entity_snp_user\Enum\SnpUserDataConstant;
use Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelperInterface;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Service\SnpContentHelperInterface;
use Drupal\sas_structure\Service\CptsHelperInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomVocabularyAccessControlHandler.
 *
 * @SuppressWarnings(PHPMD)
 */
class SnpNodeAccessControlHandler extends NodeAccessControlHandler {

  /**
   * The route match.
   *
   * @var \Drupal\sas_snp\SnpService
   */
  protected SnpService $sasSnpManager;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * If content type allowed for SNP.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected SnpContentHelperInterface $snpContentHelper;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * @var Drupal\sas_structure\Service\CptsHelperInterface
   */
  protected CptsHelperInterface $cptsHelper;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Base Database API.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Sas SNP user data Helper.
   *
   * @var \Drupal\sas_entity_snp_user\Service\SasSnpUserDataHelperInterface
   */
  protected SasSnpUserDataHelperInterface $sasSnpUserDataHelper;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->sasSnpManager = $container->get('sas_snp.manager');
    $instance->pscUser = $container->get('sas_keycloak.psc_user');
    $instance->snpContentHelper = $container->get('sas_snp.content_helper');
    $instance->sasEffectorHelper = $container->get('sas_user.effector_helper');
    $instance->sasSnpUserDataHelper = $container->get('sas_snp_user_data.helper');
    $instance->cptsHelper = $container->get('sas_structure.cpts_helper');
    $instance->cache = $container->get('cache.data');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();
    $account = $this->prepareUser($account);

    if ($entity->bundle() == SnpConstant::SAS_TIME_SLOTS && $operation === 'view') {
      /** @var \Drupal\node\NodeInterface $node_ps */
      $node_ps = current($entity->get('field_sas_time_slot_ref')
        ->referencedEntities());

      if (empty($node_ps) || !$node_ps->isPublished()) {
        $result = AccessResult::forbidden();
      }
      else {
        // For ProSante Connect User.
        $result = $this->pscUser->isValid() ? $this->hasPscAccess($node_ps) : $this->hasEffectorAccess($entity, $account);
      }
    }

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function hasPscAccess(NodeInterface $node_ps): AccessResult {
    if ($this->pscUser->get('id') != $node_ps->get('field_identifiant_rpps')->value
      && $this->pscUser->get('id') != $node_ps->get('field_personne_adeli_num')->value
    ) {
      return AccessResult::forbidden();
    }

    $access = AccessResult::forbidden();
    if ($this->sasSnpUserDataHelper->hasEditorSlotDisabled($this->pscUser->get('id'))
      && !$this->sasSnpUserDataHelper->hasParticipationVia($this->pscUser->get('id'), SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN)
    ) {
      $access = AccessResult::allowed();
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function hasEffectorAccess(EntityInterface $entity, AccountInterface $account): AccessResult {
    // Admin as access to all calendar page.
    if (in_array(SnpConstant::SAS_ADMINISTRATEUR, $account->getRoles())
      || in_array(SnpConstant::SAS_ADMINISTRATEUR_NATIONAL, $account->getRoles())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $access = AccessResult::forbidden()->cachePerUser();

    $node_ids = $this->sasSnpManager->getSnpNodesIds($account);
    $node_ids_by_time_slot = array_column($entity->get('field_sas_time_slot_ref')
      ->getValue(), 'target_id');
    $node_id_by_time_slot = !empty($node_ids_by_time_slot) ? reset($node_ids_by_time_slot) : '';

    if (!empty($node_id_by_time_slot)
      && !empty($node_ids)
      && in_array($node_id_by_time_slot, $node_ids)) {
      $access = AccessResult::allowed()->cachePerUser();
    }

    $parent_node = $this->snpContentHelper->getParent($entity);
    if (
      !empty($parent_node) &&
      $parent_node instanceof ProfessionnelDeSanteSas
    ) {
      $id_nat_parts = $parent_node->getNationalId();
      $id_nat = $id_nat_parts['id'];
      if (!empty($id_nat) && $this->isEffectorWithoutCalendar($id_nat)) {
        $access = AccessResult::forbidden()->cachePerUser();
      }
    }

    return $access;
  }

  /**
   * Check if effector must not have access to calendar.
   *
   * @param $rpps_adeli
   *   RPPS§/ADELI of user to check.
   *
   * @return bool
   *   TRUE if user does not have a calendar. FALSE else.
   */
  protected function isEffectorWithoutCalendar($rpps_adeli): bool {
    return !$this->sasSnpUserDataHelper->hasEditorSlotDisabled($rpps_adeli) ||
      $this->sasSnpUserDataHelper->hasParticipationVia($rpps_adeli, SnpUserDataConstant::SAS_PARTICIPATION_MY_SOS_MEDECIN);
  }

}
