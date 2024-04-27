<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\sas_keycloak\Service\SasKeycloakMail;
use Drupal\sas_keycloak\Service\SasKeycloakUserInfo;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the sas resend welcome email form.
 *
 * @internal
 */
class SasResendForm extends ConfirmFormBase {

  /**
   * A mail manager for sending email.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The user we want to send email.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Keycloak user info helper.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakUserInfo|null
   */
  protected ?SasKeycloakUserInfo $keycloakUserInfo;

  /**
   * The Keycloak user mail.
   *
   * @var \Drupal\sas_keycloak\Service\SasKeycloakMail
   */
  protected SasKeycloakMail $keycloakUserMail;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->keycloakUserInfo = $container->get('sas_keycloak.user_info');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->keycloakUserMail = $container->get('sas_keycloak.mail');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sas_user_resend_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    UserInterface $user = NULL
  ) {
    $this->user = $user;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      "Êtes-vous sûr(e) de vouloir renvoyer le mail de bienvenue à l'adresse %mail ?",
      ['%mail' => $this->user->getEmail()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromUserInput($this->getRedirectDestination()->get());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->entityTypeManager->getStorage('user')->load(
      $this->user->id()
    );
    $keycloak_uid = $this->keycloakUserInfo
      ->getKeycloakUid($user, FALSE);

    if (!empty($keycloak_uid)) {
      $this->keycloakUserMail->sendRegistrationEmail($keycloak_uid);
      $this->messenger()->addStatus(
        $this->t(
          'Sent email to %recipient',
          ['%recipient' => $this->user->getEmail()]
        )
      );
    }
  }

}
