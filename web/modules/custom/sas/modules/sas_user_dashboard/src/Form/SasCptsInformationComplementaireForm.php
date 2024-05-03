<?php

namespace Drupal\sas_user_dashboard\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\sas_search_index\Service\SasSearchIndexHelper;
use Drupal\sas_search_index\Service\SasSearchIndexHelperInterface;
use Drupal\sas_snp\Service\SnpContentHelper;
use Drupal\sas_user_dashboard\Services\DashboardUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SasCptsInformationComplementaireForm.
 *
 * Specific form for additional info popin.
 */
class SasCptsInformationComplementaireForm extends FormBase {

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_user_dashboard\Services\DashboardUserInterface
   */
  protected DashboardUserInterface $dashboard;

  /**
   * SasSearchIndexHelper service.
   *
   * @var \Drupal\sas_search_index\Service\SasSearchIndexHelper
   */
  protected SasSearchIndexHelper $sasSearchIndexHelper;

  /**
   * Drupal current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * UserGetDelegations service.
   *
   * @var \Drupal\sas_snp\Service\SnpContentHelper
   */
  protected SnpContentHelper $sasSnpContentHelper;

  public function __construct(
    DashboardUserInterface $dashboard,
    SasSearchIndexHelperInterface $sas_search_index_helper,
    CurrentRouteMatch $routeMatch,
    SnpContentHelper $sas_snp_content_helper
  ) {
    $this->dashboard = $dashboard;
    $this->sasSearchIndexHelper = $sas_search_index_helper;
    $this->routeMatch = $routeMatch;
    $this->sasSnpContentHelper = $sas_snp_content_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sas_user_dashboard.dashboard'),
      $container->get('sas_search_index.helper'),
      $container->get('current_route_match'),
      $container->get('sas_snp.content_helper')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId(): string {
    return 'CPTS_information_complementaire_form';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    /** @var \Drupal\node\NodeInterface $structure_node */
    $form['#structure_node'] = $this->routeMatch->getParameter('node');

    /** @var \Drupal\node\NodeInterface $snp_entity */
    $snp_entity = $this->sasSnpContentHelper->getChild($form['#structure_node']);

    $additional_info = '';
    if (!empty($snp_entity) && $snp_entity->hasField('field_sas_time_info')) {
      $additional_info = $snp_entity->get('field_sas_time_info')->value;
    }

    $form['form_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'info-complementaire-form-wrapper'],
    ];

    $form['informations_complementaires'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ajouter des informations complémentaires :'),
      '#attributes' => [
        'style' => 'margin-top: 18px;',
      ],
      '#default_value' => $additional_info,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer'),
      '#button_type' => 'btn-highlight',
      '#attributes' => [
        'class' => ['btn-info'],
      ],
      '#ajax' => [
        'callback' => '::cptsInformationComplementairesAjaxSubmit',
        'event' => 'click',
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'themes/custom/sas/css/main-sas.css';

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Récupérer le nœud passé via la route.
    $node = $form['#structure_node'];
    $additional_info = $form_state->getValue('informations_complementaires');

    $this->dashboard->handleTimeSlot($node, $additional_info);

    // Forcer l'indexation.
    $this->sasSearchIndexHelper->indexSpecificItem($node->id());

    // Redirection optionnelle après la soumission.
    $form_state->setRedirect('entity.node.canonical', ['node' => $node]);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function cptsInformationComplementairesAjaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {

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
        new ReplaceCommand('#info-complementaire-form-wrapper', $form)
      );
    }

    return $response;
  }

}
