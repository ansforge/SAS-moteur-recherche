<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the sas toggle statut for an user.
 *
 * @internal
 */
class SasToggleStatutForm extends ConfirmFormBase {

  /**
   * The user we want to send email.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sas_user_toggle_statut';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $this->user = $user;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Êtes-vous sûr(e) de vouloir passer le status à l'état '%state' ?", ['%state' => $this->user->isActive() ? 'bloqué' : 'actif']);
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

    if ($this->user->isActive()) {
      $this->user->block();
    }
    else {
      $this->user->activate();
    }
    $this->user->save();
    $this->messenger()->addStatus($this->t('Le compte %user a été $state',
      ['%user' => $this->user->getEmail(), '%state' => $this->user->isActive() ? 'activé' : 'bloqué']));

  }

}
