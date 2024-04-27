<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\sas_core\SasCoreServiceInterface;
use Drupal\sas_keycloak\Service\SasKeycloakUser;
use Drupal\sas_keycloak\Service\SasKeycloakUserInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for User email reset.
 */
class SasUserEmailResetForm extends FormBase {

  use PrivacyPolicyFormTrait;

  /**
   * SAS Core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected SasCoreServiceInterface $sasCoreService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Keycloak user helper.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUser|null
   */
  protected ?SasKeycloakUser $keycloakUser;

  /**
   * The Keycloak user info helper.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUserInfo|null
   */
  protected ?SasKeycloakUserInfo $keycloakUserInfo;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->sasCoreService = $container->get('sas_core.service');
    $instance->keycloakUser = $container->get('sas_keycloak.user');
    $instance->keycloakUserInfo = $container->get('sas_keycloak.user_info');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sas_user_email_reset';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('mail');
    $query = $this->entityTypeManager->getStorage('user')
      ->getQuery()->accessCheck()
      ->condition('mail', $email, 'IN')
      ->count();
    $countQuery = $query->execute();

    if ($countQuery) {
      $form_state->setErrorByName(
        'mail',
        $this->t(
          'ATTENTION : Votre Adresse e-mail %mail existe.',
          ['%mail' => $email]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account = $this->currentUser();
    $form['#attributes']['class'][] = 'user-form';
    $form['account']['#prefix'] = Markup::create(
      '<p>' . $this->t(
        'Tous les champs avec * sont obligatoires'
      ) . '</p><div class="form-response">'
    );
    $form['account']['#suffix'] = '</div>';

    $form['account']['mail'] = [
      '#default_value' => $account->getEmail() ?? '',
      '#type' => 'email',
      '#required' => TRUE,
      '#title' => $this->t('Adresse électronique (nom@domaine.fr)'),
      '#description' => $this->t(
        'The email address is not made public. It will only be used if you need to be contacted about your account or for opted-in notifications.'
      ),
      '#wrapper_attributes' => [
        'class' => ['float-label-item'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Enregistrer',
      '#ajax' => ['callback' => '::saveUserEmail'],
      '#states' => [
        'disabled' => [
          [':input[name="mail"]' => ['value' => $form['account']['mail']['#default_value']]],
        ],
      ],
      '#prefix' => '<div class="save-btn">',
      '#suffix' => '</div>',
    ];

    $form['actions']['back_btn'] = [
      '#type' => 'link',
      '#title' => $this->t('Retour'),
      '#url' => Url::fromRoute('sante_user.credentials'),
      '#attributes' => ['class' => ['button', 'button-secondary']],
    ];
    $this->buildPrivacyPolicyForm($form, $form_state);

    return $form;
  }

  /**
   * Set Ajax response.
   */
  public function saveUserEmail(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if (!empty($form_state->getErrors())) {
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
    }
    else {
      $form['account']['#access'] = FALSE;
      $form['success'] = [
        '#markup' => $this->t(
          "Merci, votre nouvel email a bien été enregistré."
        ),
        '#weight' => -10,
      ];
      $form['actions']['submit']['#access'] = FALSE;
      $form['privacy_policy']['#access'] = FALSE;
      $form['actions']['back_btn']['#access'] = TRUE;
    }

    $response->addCommand(new ReplaceCommand('#form-ajax-replace', $form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->currentUser();
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $data = [];

    $data['email'] = $form_state->getValue('mail');
    $user->setEmail($form_state->getValue('mail'));
    $user->save();
    if (!empty($data)) {
      $keycloak_uid = $this->keycloakUserInfo
        ->getKeycloakUid($user);

      if (!empty($keycloak_uid)) {
        // Update general user info.
        $this->keycloakUser->updateKeycloakUser(
          $keycloak_uid,
          $data
        );
      }
    }
  }

}
