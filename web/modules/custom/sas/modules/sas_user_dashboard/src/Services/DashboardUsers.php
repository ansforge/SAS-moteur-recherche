<?php

namespace Drupal\sas_user_dashboard\Services;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\sas_snp\Enum\SnpConstant;
use Drupal\sas_snp\Service\AvailabilityBlockProviderInterface;
use Drupal\sas_snp\Service\SnpContentHelper;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Drupal\sas_structure\Service\StructureHelperInterface;
use Drupal\sas_structure\Service\StructureSettingsHelperInterface;
use Drupal\user\UserInterface;

/**
 * DashboardUsers class.
 */
class DashboardUsers implements DashboardUserInterface {

  /**
   * CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * AvailabilityBlockProvider object.
   *
   * @var \Drupal\sas_snp\Service\AvailabilityBlockProviderInterface
   */
  protected AvailabilityBlockProviderInterface $availabilityBlockProvider;

  /**
   * Structure Helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelperInterface
   */
  protected StructureHelperInterface $structureHelper;

  /**
   * Structure Settings Helper.
   *
   * @var \Drupal\sas_structure\Service\StructureSettingsHelperInterface
   */
  protected StructureSettingsHelperInterface $structureSettingsHelper;

  /**
   * SOS Medecin Helper.
   *
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * @var \Drupal\sas_snp\Service\SnpContentHelperInterface
   */
  protected $snpContentHelper;

  /**
   * Constructs a database object.
   *
   *  Entity Type Manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    AvailabilityBlockProviderInterface $availabilityBlockProvider,
    StructureHelperInterface $structure_helper,
    StructureSettingsHelperInterface $structure_settings_helper,
    CurrentRouteMatch $currentRouteMatch,
    AccountProxyInterface $accountProxy,
    SosMedecinHelperInterface $sos_medecin_helper,
    SnpContentHelper $snpContentHelper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->availabilityBlockProvider = $availabilityBlockProvider;
    $this->structureHelper = $structure_helper;
    $this->structureSettingsHelper = $structure_settings_helper;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->currentUser = $accountProxy;
    $this->sosMedecinHelper = $sos_medecin_helper;
    $this->snpContentHelper = $snpContentHelper;
  }

  /**
   * Optimized get delegation set for a given account.
   *
   * @param $account
   *
   * @return array
   *   List of user account related to the given account.
   */
  public function sasUserGetDelegationsDashboardOptimized($account): array {
    $results = [];

    if ($account) {
      $query = $this->database->select('users_field_data', 'u')
        ->fields('u', ['uid']);
      $query->addJoin('left', 'user__field_sas_related_pro', 'srp', 'srp.field_sas_related_pro_target_id = u.uid');
      $query->addJoin('left', 'user__field_sas_rel_structure_manager', 'srsm',
        'srsm.field_sas_rel_structure_manager_target_id = u.uid');
      $query->condition('u.status', 1);
      $or_condition = $query->orConditionGroup()
        ->condition('srsm.entity_id', $account)
        ->condition('srp.entity_id', $account);
      $query->condition($or_condition);
      $results = $query->execute()->fetchCol();
    }

    return $results;
  }

  /**
   * List the addresses linked to the user.
   *
   * @param array $fields
   *   Field structure and professional list.
   * @param object $user
   *   User ID.
   *
   * @return array
   *   list the addresses linked to the user.
   */
  public function sasDashboardUser(array $fields, object $user): array {

    $cacheableMetadata = new CacheableMetadata();
    $addresses = [];
    foreach ($fields as $field) {
      /** @var \Drupal\node\NodeInterface $field */
      if ($field->isPublished()) {
        $cacheableMetadata->addCacheableDependency($field);

        $block_build = $this->availabilityBlockProvider->getAvailabilityBlock($field);
        $full_address = $field->get('field_address')
          ->first()
          ->getValue()['full_address'];

        $address = [
          'full_address' => $full_address,
          'link_availability_page' => $block_build,
        ];

        if ($this->structureHelper->isCds($field)) {
          $address['structure_settings_link'] = $this->structureSettingsHelper->getStructureSettingsLink($field, $user);
        }

        /** @var \Drupal\user\UserInterface $currentUserEntity */
        if (in_array(SnpConstant::SAS_DELEGATAIRE, $this->currentUser->getRoles())
          && $this->currentUser->id() != $this->currentRouteMatch->getParameter('user')) {
          unset($address['structure_settings_link']);
        }

        $addresses[] = $address;
      }
    }

    if (!$user->get('field_sas_nom')->isEmpty()) {
      $last_name = $user->get('field_sas_nom')->first()->getValue()['value'];
    }
    if (!$user->get('field_sas_prenom')->isEmpty()) {
      $first_name = $user->get('field_sas_prenom')->first()->getValue()['value'];
    }
    if (!$user->get('mail')->isEmpty()) {
      $email = $user->get('mail')->first()->getValue()['value'];
    }

    return [
      'last_name' => $last_name ?? '',
      'first_name' => $first_name ?? '',
      'email' => $email ?? '',
      'addresses' => $addresses,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getSosMedecinAssociations(array $siret_list, UserInterface $user): array {
    $association_list = [];

    foreach ($siret_list as $siret) {
      /** @var \Drupal\node\NodeInterface[] $pfg_contents */
      $pfg_contents = $this->sosMedecinHelper->getAssociationPfg($siret);
      if (!empty($pfg_contents)) {
        $pfg_list = [];

        foreach ($pfg_contents as $pfg_content) {
          $pfg_list[] = [
            'title' => $pfg_content->label(),
            'address' => $pfg_content->get('field_address')
              ->first()
              ->getValue()['full_address'],
            'telephone' => $pfg_content->get('field_telephone_fixe')->first()->getValue()['value'],
            'link_availability_page' => $this->availabilityBlockProvider->getAvailabilityBlock($pfg_content),
          ];
        }

        $association_list[] = [
          'siret' => $siret,
          'name' => reset($pfg_contents)->get('field_precision_type_eg')->value,
          'pfg_list' => $pfg_list,
          'settings_link' => $this->structureSettingsHelper->getSosMedecinAssociationSettingsLink($siret, $user),
        ];
      }
    }

    return $association_list;
  }

  /**
   * DEPRECATED - DO NOT USE.
   * Use getSettingsEntity in SasSnpUserDataHelper instead.
   *
   * Result to the entity sas_snp_user_data.
   *
   * @param string $user_id
   *   User ID.
   *
   * @return mixed
   *   result to the entity sas_snp_user_data.
   */
  public function sasGetEntitySasSnpUserData(string $user_id) {
    $query = $this->entityTypeManager->getStorage('sas_snp_user_data')
      ->getQuery()
      ->accessCheck()
      ->condition('user_id', $user_id)
      ->execute();

    $datas = '';
    if (!empty($query)) {
      $datas = $this->entityTypeManager
        ->getStorage('sas_snp_user_data')
        ->load(reset($query));
    }

    return $datas;
  }

  /**
   * @param \Drupal\node\Entity\NodeInterface $node
   * @param string $additional_info
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function handleTimeSlot(NodeInterface $node, String $additional_info): void {
    /** @var \Drupal\node\Entity\NodeStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Vérifier si un nœud SAS_TIME_SLOTS référence déjà ce nœud.
    $query = $node_storage->getQuery()->accessCheck(TRUE)
      ->condition('type', 'sas_time_slots')
      ->condition('field_sas_time_slot_ref', $node->id());

    $time_slots_ids = $query->execute();

    // Charger le nœud existant s'il y a des IDs de créneaux horaires, sinon créer un nouveau nœud.
    $time_slots_node = !empty($time_slots_ids)
      ? $node_storage->load(reset($time_slots_ids))
      : $node_storage->create([
        'type' => 'sas_time_slots',
        'title' => sprintf('sas_snp_%s', $node->id()),
        'field_sas_time_slot_ref' => ['target_id' => $node->id()],
        'uid' => 0,
        'status' => NodeInterface::PUBLISHED,
      ]);

    // Mettre à jour le champ field_sas_time_info avec le texte du formulaire.
    $time_slots_node->set('field_sas_time_info', $additional_info);

    // Sauvegarder le nœud.
    $time_slots_node->save();
  }

  /**
   * Get additional information from a node referenced in a time slot.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array|null
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getTimeSlotAdditionalInfo(NodeInterface $node): ?array {

    // Searching for a SAS_TIME_SLOTS node referencing the provided node.
    $node_ref = $this->snpContentHelper->getChild($node);
    if (!empty($node_ref)) {
      // Return the value of the additional information field.
      $additional_info_value = $node_ref->get('field_sas_time_info')->first() ?
        $node_ref->get('field_sas_time_info')->first()->getValue()['value'] : '';
      return [
        'additional_info' => $additional_info_value,
      ];
    }

    return NULL;
  }

}
