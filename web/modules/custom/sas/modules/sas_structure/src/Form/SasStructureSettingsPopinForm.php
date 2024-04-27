<?php

namespace Drupal\sas_structure\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\sas_search_index\Service\SasSearchIndexHelperInterface;
use Drupal\sas_structure\Entity\SasStructureSettings;
use Drupal\sas_structure\Enum\StructureConstant;
use Drupal\sas_structure\Enum\StructureLabelConstant;
use Drupal\sas_structure\Service\SosMedecinHelperInterface;
use Drupal\sas_structure\Service\StructureSettingsHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SasStructureSettingsPopinForm.
 *
 * Specific form for structure settings popin.
 *
 * @package Drupal\sas_structure\Form
 */
class SasStructureSettingsPopinForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\sas_search_index\Service\SasSearchIndexHelperInterface
   */
  protected SasSearchIndexHelperInterface $sasSearchIndexHelper;

  /**
   * @var \Drupal\sas_structure\Service\StructureSettingsHelperInterface
   */
  protected StructureSettingsHelperInterface $structureSettingsHelper;

  /**
   * @var \Drupal\sas_structure\Service\SosMedecinHelperInterface
   */
  protected SosMedecinHelperInterface $sosMedecinHelper;

  /**
   * SasStructureSettingsPopinForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SasSearchIndexHelperInterface $sas_search_index_helper,
    StructureSettingsHelperInterface $structure_settings_helper,
    SosMedecinHelperInterface $sos_medecin_helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sasSearchIndexHelper = $sas_search_index_helper;
    $this->structureSettingsHelper = $structure_settings_helper;
    $this->sosMedecinHelper = $sos_medecin_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('sas_search_index.helper'),
      $container->get('sas_structure.settings_helper'),
      $container->get('sas_structure.sos_medecin')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'structure_settings_popin_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Specific for SOS Medecin Association.
    if ($this->getRouteMatch()->getRouteName() === 'entity.sas_structure_settings.sos_medecin.edit') {
      $structure_id_nat = $this->getRouteMatch()->getParameter('siret');
      $id_type = StructureConstant::ID_TYPE_SIRET;
    }
    else {
      /** @var \Drupal\node\NodeInterface $structure_node */
      $structure_node = $this->getRouteMatch()->getParameter('node');
      $structure_id_nat = $structure_node->get('field_identifiant_finess')->value;
      $id_type = StructureConstant::ID_TYPE_FINESS;
    }

    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings */
    $structure_settings = $this->entityTypeManager->getStorage('sas_structure_settings')->loadByStructureId($structure_id_nat);
    if (!empty($structure_settings)) {
      // Store origin settings for validation.
      $form['#structure_settings'] = $structure_settings;
    }

    $form['#title'] = 'Vos paramètres';
    $form['#attributes'] = [
      'class' => ['service-status-form'],
    ];
    $form['#prefix'] = '<div id="service-status-form-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'sas_structure/structure-settings-popin';

    $form['structure_id'] = [
      '#type' => 'hidden',
      '#value' => $structure_id_nat,
    ];

    $form['id_type'] = [
      '#type' => 'hidden',
      '#value' => $id_type,
    ];

    if (!empty($structure_node)) {
      $form['structure_nid'] = [
        '#type' => 'hidden',
        '#value' => $structure_node->id(),
      ];
    }

    // Status message.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    if ($id_type === StructureConstant::ID_TYPE_SIRET) {
      $this->getSosMedecinFormFields($form);
    }
    else {
      $this->getStructureFormFields($form);
    }

    $form['action_button'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['wrapper-btn-actions'],
      ],
      'cancel' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t(StructureLabelConstant::FORM_CANCEL),
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'btn-highlight-outline',
            'btn-cancel',
            'js-btn-cancel',
          ],
        ],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t(StructureLabelConstant::FORM_SAVE),
        '#ajax' => [
          'callback' => '::structureSettingsPopinAjaxSubmit',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Add form element for "general" structure settings.
   *
   * @param array $form
   *   Settings form.
   */
  protected function getStructureFormFields(array &$form) {
    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings */
    $structure_settings = $form['#structure_settings'];

    $form['sas_participation_status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t(StructureLabelConstant::FORM_MAIN_SECTION_TITLE),
      'practitioner_count' => [
        '#type' => 'number',
        '#title' => $this->t(StructureLabelConstant::FORM_PRACTITIONER_COUNT),
        '#min' => 0,
        '#default_value' => !empty($structure_settings) && !$structure_settings->get('practitioner_count')->isEmpty()
          ? $structure_settings->get('practitioner_count')->value
          : 0,
      ],
      'practitioner_count_message_wrapper' => [
        '#type' => 'container',
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t(StructureLabelConstant::FORM_PRACTITIONER_COUNT_MESSAGE),
          '#attributes' => [
            'class' => ['practitioner-count-message'],
          ],
        ],
        '#states' => [
          'visible' => [
            [
              [':input[name="practitioner_count"]' => ['value' => '0']],
              'or',
              [':input[name="practitioner_count"]' => ['value' => '']],
            ],
          ],
        ],
      ],
      'sas_participation' => [
        '#type' => 'checkbox',
        '#title' => $this->t(StructureLabelConstant::FORM_SAS_PARTICIPATION_CHECKBOX),
        '#default_value' => !empty($structure_settings) && $structure_settings->get('sas_participation')->value,
        '#states' => [
          'invisible' => [
            [
              [':input[name="practitioner_count"]' => ['value' => '0']],
              'or',
              [':input[name="practitioner_count"]' => ['value' => '']],
            ],
          ],
        ],
      ],
      'participation_sas_details' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['participation-sas-details-wrapper'],
        ],
        '#states' => [
          'invisible' => [
            [':input[name="sas_participation"]' => ['checked' => FALSE]],
            'or',
            [':input[name="practitioner_count"]' => ['value' => '0']],
            'or',
            [':input[name="practitioner_count"]' => ['value' => '']],
          ],
        ],
        'hours_available' => [
          '#type' => 'checkbox',
          '#title' => $this->t(StructureLabelConstant::FORM_HOUR_DECLARATION_CHECKBOX),
          '#default_value' => !empty($structure_settings) &&
          $structure_settings->get('sas_participation')->value &&
          $structure_settings->get('hours_available')->value,
          '#states' => [
            'invisible' => [
              [':input[name="sas_participation"]' => ['checked' => FALSE]],
              'or',
              [':input[name="practitioner_count"]' => ['value' => '0']],
              'or',
              [':input[name="practitioner_count"]' => ['value' => '']],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Add form element for SOS Medecin settings.
   *
   * @param array $form
   *   Settings form.
   */
  protected function getSosMedecinFormFields(array &$form) {
    $form['sas_participation_status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t(StructureLabelConstant::FORM_MAIN_SECTION_TITLE_SOS_MEDECIN),
      'hours_available' => [
        '#type' => 'checkbox',
        '#title' => $this->t(StructureLabelConstant::FORM_SOS_MEDECIN_HOUR_DECLARATION_CHECKBOX),
        '#default_value' => !empty($form['#structure_settings']) &&
        $form['#structure_settings']->get('hours_available')->value,
        '#states' => [
          'visible' => [
            ':input[name="sas_participation"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
  }

  /**
   * Access control to form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   Access result.
   */
  public function access(RouteMatchInterface $route_match) {
    // Default access control for all other structure type.
    $structure_node = $route_match->getParameter('node');

    $access_result = !empty($structure_node) && $this->structureSettingsHelper->checkSettingsUpdateAccess($structure_node)
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    // Specific access control for SOS Medecin settings.
    if ($route_match->getRouteName() === 'entity.sas_structure_settings.sos_medecin.edit') {
      $siret = $route_match->getParameter('siret');

      $access_result = !empty($siret) && $this->structureSettingsHelper->checkSosMedecinSettingsUpdateAccess($siret)
        ? AccessResult::allowed()
        : AccessResult::forbidden();
    }

    return $access_result->cachePerUser();
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
    $values = $form_state->getValues();

    if (empty($values['structure_id']) || empty($values['id_type'])) {
      $form_state->setErrorByName(NULL, StructureLabelConstant::FORM_ERROR_MISSING_STRUCTURE_ID);
    }

    if (!empty($values['sas_participation']) && empty($values['hours_available'])) {
      $form_state->setErrorByName(
        'sas_participation_status][participation_sas_details][hours_available',
        StructureLabelConstant::FORM_ERROR_HOUR_DECLARATION_REQUIRED
      );
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $change_log = [];
    $values = $form_state->getValues();

    /** @var \Drupal\sas_structure\Entity\SasStructureSettings $structure_settings */
    // Create new SasStructureSettings entity if not exists.
    $structure_settings = !empty($form['#structure_settings'])
      ? $form['#structure_settings']
      : SasStructureSettings::create(
        [
          'structure_id' => $values['structure_id'],
          'id_type' => $values['id_type'],
        ]
      );

    $sas_participation = $values['id_type'] === StructureConstant::ID_TYPE_SIRET
      ? $values['hours_available']
      : $values['practitioner_count'] > 0 && $values['sas_participation'];

    // Prepare data to save.
    $change_log['sas_participation'] = $structure_settings->get('sas_participation')->value == $values['sas_participation']
      ? 'No change'
      : $values['sas_participation'];
    $structure_settings->set('sas_participation', $sas_participation);
    $structure_settings->set('hours_available', $sas_participation && !empty($values['hours_available']));
    $structure_settings->set('practitioner_count', $values['practitioner_count']);
    $structure_settings->set('updated', time());
    $structure_settings->set('uid', $this->currentUser()->id());

    try {
      $structure_settings->save();
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addError('L\'enregistrement des paramètres a échoué.');
      $this->logger('SAS Structure Settings')->error('Error while saving SasStructureSettings entity.</br>Error @error_code : @error_message', [
        '@error_code' => $e->getCode(),
        '@error_message' => $e->getMessage(),
      ]);
    }

    // Log changes.
    $message = <<<eof
Structure settings update :<br />
Date : @date<br />
User id : @uid<br />
Structure ID : @structure_id<br />
Sas Participation : @sas_participation<br />
Practitioner count : @practitioner_count<br />
eof;

    $this->logger('SAS Structure Settings')->info($message, [
      '@date' => date('Y/m/d H:i:s'),
      '@uid' => $this->currentUser()->id(),
      '@structure_id' => $values['structure_id'],
      '@sas_participation' => $change_log['sas_participation'],
      '@practitioner_count' => $values['practitioner_count'],
    ]);

    $this->indexItems($values);
  }

  /**
   * Ajax submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function structureSettingsPopinAjaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $user_id = $this->getRequest()->get('user_id');
    if (empty($user_id)) {
      $user_id = $this->currentUser()->id();
    }

    if ($form_state->isExecuted()) {
      // Go back to dashboard.
      $response->addCommand(
        new RedirectCommand(Url::fromRoute('sas_user_dashboard.gestionnaire_de_structure',
          ['user' => $user_id])->toString())
      );
    }
    else {
      // Reload form.
      $response->addCommand(
        new ReplaceCommand('#service-status-form-wrapper', $form)
      );
    }

    return $response;
  }

  /**
   * Index items after settings update.
   *
   * @param array $values
   *   New settings values.
   */
  public function indexItems(array $values) {
    if (empty($values['id_type'])) {
      return;
    }

    $ids_to_index = [];
    // Re-index associated node.
    if ($values['id_type'] === StructureConstant::ID_TYPE_FINESS && !empty($values['structure_nid'])) {
      $ids_to_index[] = $values['structure_nid'];
    }

    if (
      $values['id_type'] === StructureConstant::ID_TYPE_SIRET
      && !empty($values['structure_id'])
      && $this->sosMedecinHelper->isSosMedecinAssociation($values['structure_id'])
    ) {
      $ids_to_index = $this->sosMedecinHelper->getAssociationPfg($values['structure_id'], FALSE);
    }

    if (!empty($ids_to_index)) {
      foreach ($ids_to_index as $id) {
        try {
          $this->sasSearchIndexHelper->indexSpecificItem($id);
        }
        catch (\Exception $e) {
          $this->logger('SAS Structure')
            ->error('Error while indexing structure.');
        }
      }
    }
  }

}
