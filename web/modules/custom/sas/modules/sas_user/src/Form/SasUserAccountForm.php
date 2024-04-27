<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\sante_user\Form\UserAccountForm;
use Drupal\sante_user\PrivacyPolicyNotice;
use Drupal\sas_core\SasCoreService;
use Drupal\sas_user\Service\SasAccountFormsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User account form for SAS users.
 */
class SasUserAccountForm extends UserAccountForm {

  /**
   * @var \Drupal\sas_core\SasCoreService
   */
  protected SasCoreService $sasCoreService;

  /**
   * @var \Drupal\sas_user\Service\SasAccountFormsHelper
   */
  protected SasAccountFormsHelper $sasAccountFormsHelper;

  /**
   * Construct specific user account form for SAS.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Render\Renderer $renderer
   * @param \Drupal\sante_user\PrivacyPolicyNotice $policyNotice
   * @param \Drupal\sas_core\SasCoreService $sasCoreService
   * @param \Drupal\sas_user\Service\SasAccountFormsHelper $sasAccountFormsHelper
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    Renderer $renderer,
    PrivacyPolicyNotice $policyNotice,
    SasCoreService $sasCoreService,
    SasAccountFormsHelper $sasAccountFormsHelper
  ) {
    parent::__construct(
      moduleHandler: $moduleHandler,
      renderer: $renderer,
      policyNotice: $policyNotice
    );
    $this->sasCoreService = $sasCoreService;
    $this->sasAccountFormsHelper = $sasAccountFormsHelper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('sante_user.privacy_policy_notice'),
      $container->get('sas_core.service'),
      $container->get('sas_user.account_forms_helper')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->sasCoreService->isSasContext()) {
      unset($form['pwd_link']);

      $pwd_button = $this->sasAccountFormsHelper->getRenewButtonOverride();
      if (!empty($pwd_button)) {
        $form['pwd_link'] = $pwd_button;
      }
    }

    return $form;
  }

  /**
   * Ajax callback for password renew.
   *
   * @param array $form
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function passwordRenewSubmit(array &$form) {
    $response = new AjaxResponse();

    $this->sasAccountFormsHelper->makeRenewEmailSendAction();

    $form['success'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Vous allez recevoir un mail pour la réinitialisation de votre mot de passe.'),
      '#weight' => -10,
      '#attributes' => [
        'class' => ['messages--status'],
        'role' => 'status',
      ],
    ];

    $response->addCommand(new ReplaceCommand('#block-sas-adminimal-system-main', $form));
    return $response;
  }

}
