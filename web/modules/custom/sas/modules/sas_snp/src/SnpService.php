<?php

namespace Drupal\sas_snp;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Manager\SasAvailabilityManager;
use Drupal\sas_snp\Service\SnpContentHelperInterface;
use Drupal\sas_snp\Service\SnpUnavailabilityHelper;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Service\CptsHelperInterface;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Drupal\sas_user\Service\SasEffectorHelperInterface;
use Drupal\sas_user\Service\SasStructureManagerHelper;

/**
 * The Class SnpService.
 */
class SnpService {

  /**
   * The sas_api_client.service service.
   *
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected $sasApiClientService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SOS Médecin helper service.
   *
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * Sas user helper.
   *
   * @var \Drupal\sas_user\Service\SasEffectorHelperInterface
   */
  protected SasEffectorHelperInterface $sasEffectorHelper;

  /**
   * Constructs a ContentTranslationOverviewAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */

  /**
   * If content type allowed for SNP.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected SnpContentHelperInterface $snpContentHelper;

  /**
   * The SAS Availability Manger.
   *
   * @var \Drupal\sas_snp\Manager\SasAvailabilityManager
   */
  protected SasAvailabilityManager $availabilityManager;

  /**
   * SnpUnavailabilityHelper service.
   *
   * @var \Drupal\sas_snp\Service\SnpUnavailabilityHelper
   */
  protected SnpUnavailabilityHelper $sasSnpUnavailabilityHelper;

  /**
   * @var Drupal\sas_structure\Service\CptsHelperInterface
   */
  protected CptsHelperInterface $cptsHelper;

  /**
   * @var \Drupal\sas_user\Service\SasStructureManagerHelper
   */
  protected SasStructureManagerHelper $sasStuctureManagerHelper;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ClientEndpointPluginManager $sas_api_client_service,
    SosMedecinHelperInterface $sos_medecin_helper,
    SasEffectorHelperInterface $sasEffectorHelper,
    SnpContentHelperInterface $snpContentHelper,
    SasAvailabilityManager $availabilityManager,
    SnpUnavailabilityHelper $sasSnpUnavailabilityHelper,
    SasStructureManagerHelper $sasStructureManagerHelper
  ) {
    $this->sasApiClientService = $sas_api_client_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->sosMedecinHelper = $sos_medecin_helper;
    $this->sasEffectorHelper = $sasEffectorHelper;
    $this->snpContentHelper = $snpContentHelper;
    $this->availabilityManager = $availabilityManager;
    $this->sasSnpUnavailabilityHelper = $sasSnpUnavailabilityHelper;
    $this->sasStuctureManagerHelper = $sasStructureManagerHelper;
  }

  /**
   * {@inheritdoc}
   */
  public function getSnpNodesIds($account) {

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());

    $node_ids = [];
    if ($user->hasRole(SnpConstant::SAS_EFFECTEUR)) {
      $node_ids = $this->getNidRpps($user);
    }

    if ($user->hasRole(SnpConstant::SAS_GESTIONNAIRE_STRUCTURE)) {
      // Get structure content ID.
      $node_ids = array_merge($node_ids, array_column($user->get('field_sas_attach_structures')->getValue(), 'target_id'));

      // Get PS content ID attach to structure manager CPTS.
      $nids_list = $this->sasStuctureManagerHelper->getCptsPlaceNids($user);
      $node_ids = array_merge($node_ids, $nids_list);

      // Get SOS medecin "Point fixe de garde" content ID.
      $siret_list = array_column($user->get(StructureConstant::SOS_MEDECIN_USER_FIELD_NAME)->getValue(), 'value');
      if (!empty($siret_list)) {
        foreach ($siret_list as $siret) {
          $node_ids = array_merge($node_ids,
            $this->sosMedecinHelper->getAssociationPfg($siret, FALSE));
        }
      }
    }

    if ($user->hasRole(SnpConstant::SAS_DELEGATAIRE)) {
      $related_user_ids = array_column($user->get('field_sas_related_pro')->getValue(), 'target_id');
      if (!empty($related_user_ids)) {
        $related_users = $this->entityTypeManager->getStorage('user')->loadMultiple($related_user_ids);
        foreach ($related_users as $related_user) {
          $nid_rpps = $this->getNidRpps($related_user);
          $node_ids = array_merge($node_ids, $nid_rpps);
          $node_ids = array_merge($node_ids, array_column($related_user->get('field_sas_attach_structures')->getValue(), 'target_id'));
        }

      }
      $related_user_ids = array_column($user->get('field_sas_rel_structure_manager')->getValue(), 'target_id');
      if (!empty($related_user_ids)) {
        $related_users = $this->entityTypeManager->getStorage('user')->loadMultiple($related_user_ids);
        foreach ($related_users as $related_user) {
          $nid_rpps = $this->getNidRpps($related_user);
          $node_ids = array_merge($node_ids, $nid_rpps);
          $node_ids = array_merge($node_ids, array_column($related_user->get('field_sas_attach_structures')->getValue(), 'target_id'));
        }
      }
    }

    return $node_ids;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.MissingImport)
   */
  public function updateSnpAvailability(Node $node) {
    if ($node->bundle() === 'sas_time_slots') {
      $available = FALSE;
      if ($node->get('field_sas_time_slot_schedule_id')->isEmpty()) {
        return NULL;
      }
      $schedule_id = $node->get('field_sas_time_slot_schedule_id')->first()->getValue()['value'];
      if (!empty($schedule_id)) {

        // Start day is today at midnight.
        $start_date = new \DateTimeImmutable(
          datetime: 'today',
          timezone: new \DateTimeZone('+0100')
        );
        // End date is two day after at 23:59:59.
        $end_date = $start_date
          ->modify('+2 days +23 hours +59 minutes +59 seconds')
          ->format(DATE_ATOM);
        $start_date = $start_date->format(DATE_ATOM);

        $response = $this->sasApiClientService->sas_api('schedule', [
          'query' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
          ],
          'tokens' => [
            'id' => $schedule_id,
          ],
        ]);

        $is_in_vacation = $this->sasSnpUnavailabilityHelper->isInVacationNextThreeDays($node);
        if (!empty($response)&& !$is_in_vacation) {
          $available = TRUE;
        }
      }

      $parent_node = $this->snpContentHelper->getParent($node);
      if (!empty($parent_node)) {
        $this->availabilityManager->updateHasSnpAvailability($parent_node->id(), $available);
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getNidRpps($related_user) {
    $nid_rpps = [];
    if ($related_user->get('field_sas_rpps_adeli')->value) {
      $user_id = $this->sasEffectorHelper->getEffectorIdParts($related_user->get('field_sas_rpps_adeli')->value);
      $prefix = $user_id['prefix'];
      $rpps_adeli = $user_id['id'];
      $nid_rpps = $this->sasEffectorHelper->getContentByRppsAdeli($rpps_adeli, $prefix);
    }
    return $nid_rpps;
  }

}
