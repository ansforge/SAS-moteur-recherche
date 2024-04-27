<?php

namespace Drupal\sas_entity_snp_user\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sas_api_client\Plugin\ClientEndpointPluginManager;
use Drupal\sas_entity_snp_user\Entity\SasSnpUserData;
use Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface;
use Drupal\sas_structure\Service\StructureHelper;

/**
 * Class SasSnpUserDataHelper.
 *
 * Provide helpers to get sas user data.
 *
 * @package Drupal\sas_entity_snp_user\Service
 */
class SasSnpUserDataHelper implements SasSnpUserDataHelperInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * ProSanteConnect user manager.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface
   */
  protected SasKeycloakPscUserInterface $pscUser;

  /**
   * Structure helper.
   *
   * @var \Drupal\sas_structure\Service\StructureHelper
   */
  protected StructureHelper $structureHelper;

  /**
   * @var \Drupal\sas_api_client\Plugin\ClientEndpointPluginManager
   */
  protected ClientEndpointPluginManager $sasClient;

  /**
   * SasSnpUserDataHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\sas_keycloak\Service\SasKeycloakPscUserInterface $psc_user
   *   ProSanteConnect user helper.
   * @param \Drupal\sas_structure\Service\StructureHelper $structureHelper
   *   StructureHelper user helper.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    SasKeycloakPscUserInterface $psc_user,
    StructureHelper $structureHelper,
    ClientEndpointPluginManager $sas_client
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->pscUser = $psc_user;
    $this->structureHelper = $structureHelper;
    $this->sasClient = $sas_client;
  }

  /**
   * {@inheritDoc}
   */
  public function getSettingsBy(array $filters, bool $first = TRUE, bool $toArray = TRUE): SasSnpUserData|array {
    try {
      $result = $this->entityTypeManager
        ->getStorage('sas_snp_user_data')
        ->loadByProperties($filters);
    }
    catch (\Exception $e) {
      return [];
    }

    if (empty($result)) {
      return [];
    }

    if ($toArray) {
      foreach ($result as $key => $settings) {
        $settings = $settings->toArray();

        // cpts_location is serialized data not manage with map type field.
        if (!empty($settings['cpts_locations'][0]['value'])) {
          $settings['cpts_locations'][0]['value'] = unserialize(
            $settings['cpts_locations'][0]['value'],
            ['allowed_classes' => FALSE]
          );
        }

        $result[$key] = $settings;
      }
    }

    return $first ? reset($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsEntity(string $id_nat): ?SasSnpUserData {
    try {
      $settings = $this->entityTypeManager
        ->getStorage('sas_snp_user_data')
        ->loadByProperties(['user_id' => $id_nat]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return NULL;
    }

    if (empty($settings)) {
      return NULL;
    }

    return reset($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function hasEditorSlotDisabled(string $id_nat): bool {
    $settings = $this->getSettingsEntity($id_nat);

    return !empty($settings)
      && $settings->hasField('editor_disabled')
      && !$settings->get('editor_disabled')->isEmpty()
      && !empty($settings->get('editor_disabled')->value);
  }

  public function hasParticipationVia(string $id_nat, int $participation_via): bool {
    /** @var \Drupal\sas_entity_snp_user\Entity\SasSnpUserData $settings */
    $settings = $this->getSettingsEntity($id_nat);

    return !empty($settings)
      && $settings->hasField('participation_sas_via')
      && !$settings->get('participation_sas_via')->isEmpty()
      && $settings->get('participation_sas_via')->value == $participation_via;
  }

  /**
   * {@inheritDoc}
   */
  public function getTermIdsByEstablishmentType(string $taxonomyType = ''): array {
    $termNames = match ($taxonomyType) {
      'msp' => [
        'Maison de santé (L.6223-3)',
        'Maison de santé (L6223-3)',
      ],
      'cpts' => [
        'Communauté Professionnelle Territoriale de Santé (CPTS)',
      ],
      'cds' => [
        'Centre de santé',
        'Centre de Santé',
      ],
      default => [
        'Centre de santé',
        'Centre de Santé',
        'Maison de santé (L.6223-3)',
        'Maison de santé (L6223-3)',
      ],
    };

    try {
      $results = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $termNames,
          'vid' => [
            'establishment_type_ror',
            'type_etablissement_finess',
            'establishment_type',
          ],
        ]);
    }
    catch (\Exception $e) {
      return [];
    }

    return !empty($results) ? array_keys($results) : [];
  }

  /**
   * Check value to be a valid finess.
   *
   * @param array $form_state
   *   Form state containing values to check.
   * @param string $field_to_check
   *   Field name to check.
   */
  public function sasFinessAutocompleteValidation(FormStateInterface &$form_state, string $field_to_check) {
    $values = $form_state->getValues();
    // Check if field as been filled.
    if (empty($values[$field_to_check])) {
      $form_state->setErrorByName($field_to_check, $this->t('Veuillez sélectionner une structure (CPTS ou MSP).'));
    }

    // Check if filled data has a finess number.
    $finess_match = [];
    if (preg_match('/.*\(([0-9]+)\)$/', $values[$field_to_check], $finess_match) == FALSE) {
      $form_state->setErrorByName(
        $field_to_check,
        "Le numéro FINESS de la structure n'est pas présent dans le champ."
      );
    }
    else {
      // Check if found finess is valid in databases.
      $finess = $finess_match[1];
      $finess_data = $this->structureHelper->getStructureDataByFiness($finess);
      if (empty($finess_data)) {
        $form_state->setErrorByName(
          $field_to_check,
          "Le numéro FINESS de la structure n'existe pas sur le site."
        );
      }
    }

  }

  /**
   * Get editor list.
   */
  public function getEditorList() {
    $editorsSoftware = $this->sasClient->aggregator('editors', [
      'query' => [
        'group' => 'id-name',
        'is_active' => 1,
      ],
    ]);
    $optionEditor = [];
    foreach ($editorsSoftware as $editorSoftware) {
      $optionEditor[$editorSoftware['id']] = $editorSoftware['corporateName'];
    }
    return $optionEditor;
  }

  /**
   * Get effector editors.
   */
  public function getEffectorEditors($userId) {
    $editorsUser = $this->sasClient->aggregator('practitioner_national_id', [
      'tokens' => [
        'id' => $userId,
      ],
    ]);

    $defaultEditorUsers = [];

    if (!empty($editorsUser)) {
      foreach ($editorsUser as $editorUser) {
        $defaultEditorUsers[$editorUser['editor']['id']] = $editorUser['editor']['corporateName'];
      }
    }

    return $defaultEditorUsers;
  }

  /**
   * Get rpps or adeli from places.
   */
  public function getRppsAdeliFromPlaces($user_places) {
    foreach ($user_places as $place) {
      if ($place->hasField('field_identifiant_rpps') && !$place->get('field_identifiant_rpps')
        ->isEmpty()) {
        $rppsUser = $place->get('field_identifiant_rpps')->value;
      }
      elseif ($place->hasField('field_personne_adeli_num') && !$place->get('field_personne_adeli_num')
        ->isEmpty()) {
        $adeliUser = $place->get('field_personne_adeli_num')->value;
      }
    }
    return $rppsUser ?: ($adeliUser ?: NULL);
  }

}
