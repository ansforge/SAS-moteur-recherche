<?php

namespace Drupal\sas_user\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sas_user\Service\SasUserHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SasUserAddingDelegataire class.
 */
class SasUserAddingDelegataire extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Sas Territory service.
   *
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected $sasTerritories;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * DashboardUser service.
   *
   * @var \Drupal\sas_user\Service\SasUserHelperInterface
   */
  protected SasUserHelperInterface $userHelper;

  /**
   * The token manager.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->sasTerritories = $container->get('term.territory');
    $instance->database = $container->get('database');
    $instance->emailValidator = $container->get('email.validator');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->userHelper = $container->get('sas_user.helper');
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sas_user_adding_delegataire';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => new FormattableMarkup('@title&nbsp;<span class="form-required" title="Ce champ est requis.">*</span>', [
        '@title' => $this->t("Nom"),
      ]),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => new FormattableMarkup('@title&nbsp;<span class="form-required" title="Ce champ est requis.">*</span>', [
        '@title' => $this->t("Prénom"),
      ]),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['city'] = [
      '#type' => 'entity_autocomplete',
      '#title' => new FormattableMarkup('@title&nbsp;<span class="form-required" title="Ce champ est requis.">*</span>', [
        '@title' => $this->t("Ville d’exercice"),
      ]),
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'cities_autocomplete',
          'display_name' => 'entity_reference_1',
          'arguments' => [],
        ],
      ],
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => new FormattableMarkup('@title&nbsp;<span class="form-required" title="Ce champ est requis.">*</span>', [
        '@title' => $this->t("E-mail du délégataire"),
      ]),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Valider'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->emailValidator->isValid($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t('ATTENTION : Adresse email invalide'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Extracts the entity ID from the autocompletion result.
    $term_id = $form_state->getValue('city');

    if (!empty($term_id)) {
      $administrators = $this->userHelper->RetrieveAccountAdministrators($term_id);
    }
    else {
      $this->messenger()
        ->addError($this->t("Sélectionnez votre ville."));
      return;
    }

    if (empty($administrators)) {
      $this->messenger()
        ->addError($this->t("Il n'existe actuellement aucun administrateur pour votre région."));
      return;
    }

    $data = [];

    foreach ($administrators as $to) {
      $factory = $this->config('sas_config.user_account');
      $body = empty($factory->get('texts')['delegation_request']['mail']) ? $this->t("Body d'email non configurer en BO") : $factory
        ->get('texts')['delegation_request']['mail'];

      $data['sas_form_adding_delegataire'] = [
        'lastname' => $form_state->getValue('lastname'),
        'firstname' => $form_state->getValue('firstname'),
        'city' => $form_state->getValue('city'),
        'email' => $form_state->getValue('email'),
        'possede_un_compte' => $this->userHelper->sasDelegataireExist($form_state->getValue('email')) ? 'oui' : 'non',
      ];

      $body = $this->token->replace($body, $data, ['clear' => TRUE]);
      $params = [
        'body' => $body,
        'subject' => $this->t('Demande de délégation'),
      ];

      $module = 'sas_user';
      $key = 'sas_user_adding_delegataire';
      $langCode = $this->currentUser()->getPreferredLangcode();
      // Send mail.
      $this->mailManager->mail($module, $key, $to, $langCode, $params, NULL, TRUE);
    }

    $this->messenger()
      ->addStatus($this->t("Votre demande a bien été envoyée à votre administrateur territorial."));
  }

}
