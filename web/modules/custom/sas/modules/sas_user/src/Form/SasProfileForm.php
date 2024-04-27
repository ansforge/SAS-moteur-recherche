<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\ProfileForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the The sas user profile form.
 *
 * @internal
 */
class SasProfileForm extends ProfileForm {

  use SasUserProfileFormTrait;
  use SasUserFieldsFormTrait;
  use SasUserValidateFormTrait;
  use SasUserSubmitFormTrait;

  /**
   * Sas User service.
   *
   * @var \Drupal\sas_user\Service\SasUserHelper
   */
  protected $sasUserHelper;

  /**
   * Drupal nodeStorage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Drupal termStorage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Drupal userStorage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Sas Territory service.
   *
   * @var \Drupal\sas_territory\Services\SasGetTermCodeCitiesInterface
   */
  protected $sasTerritories;

  /**
   * Sas config manager.
   *
   * @var \Drupal\sas_config\SasApiConfigManagerInterface
   */
  protected $sasConfigManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->sasUserHelper = $container->get('sas_user.helper');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->nodeStorage = $container->get('entity_type.manager')->getStorage('node');
    $instance->termStorage = $container->get('entity_type.manager')->getStorage('taxonomy_term');
    $instance->userStorage = $container->get('entity_type.manager')->getStorage('user');
    $instance->sasTerritories = $container->get('term.territory');
    $instance->sasConfigManager = $container->get('sas_config.service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // User role_change field for all users.
    $form['account']['roles']['#access'] = FALSE;
    $form['account']['roles']['#required'] = TRUE;

    $form['role_change']['widget']['#required'] = TRUE;

    $this->buildSasUserFieldStates($form);
    $this->buildSasUserFieldReferences($form);
    $this->buildProfileForm($form, $form_state);
    $form['#process'][] = '::processProfileForm';
    $form['#after_build'][] = '::afterBuildSasUserForm';

    $form['#password_policy_skip_empty'] = TRUE;
    $form['#attached']['library'][] = 'sas_user/sas_roles_regulators_info';
    $form['#attached']['library'][] = 'chosen.claro';
    return $form;
  }

  /**
   * Provide some form alter for SAS user registration.
   *
   * @param array $form
   *   THe current form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function buildProfileForm(array &$form, FormStateInterface $form_state) {
    $keys = ['notify', 'status', 'name', 'current_pass'];
    $form['account']['notify']['#default_value'] = TRUE;
    foreach ($keys as $key) {
      $form['account'][$key]['#access'] = FALSE;
    }

    $form['account']['mail']['#description'] = $this->t(
    // phpcs:ignore
      "Une adresse électronique valide. Le système enverra tous les courriels à cette adresse. L'adresse électronique ne sera pas rendue publique et ne sera utilisée que pour la réinitialisation du mot de passe." //NOSONAR
    );
    $form['account']['roles']['#description'] = $this->t("Veuillez choisir le(s) rôle(s) pour l'utilisateur.");
    $form['role_change']['widget']['#description'] = $this->t("Veuillez choisir le(s) rôle(s) pour l'utilisateur.");
    $cgu_config = $this->sasConfigManager->getConfigByGroup('cgu')[0];
    $notice_profile = !empty($cgu_config) && !empty($cgu_config['value']['notice_osnp_ioa']['value'])
      ? '<div id="notice-osnp-ioa" class="hidden">' . $cgu_config['value']['notice_osnp_ioa']['value'] . '</div>'
      : '';
    $form['role_change']['#suffix'] = $notice_profile;

    $form['account']['mail']['#required'] = TRUE;
    $form['field_sas_nom']['widget'][0]['value']['#required'] = TRUE;
    $form['field_sas_prenom']['widget'][0]['value']['#required'] = TRUE;
    $form['field_sas_user_sas']['#disabled'] = TRUE;
    $form['field_sas_territoire']['widget']['#chosen'] = TRUE;
    if (isset($form['field_sas_territoire']['widget']['#options']['_none'])) {
      unset($form['field_sas_territoire']['widget']['#options']['_none']);
    }
    $currentUserEntity = $this->entityTypeManager->getStorage('user')
      ->load($this->entity->id());
    if ($form['field_sas_nom'] ?? NULL) {
      $form['field_sas_nom']['#states']['required'] = [
        ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => FALSE],
      ];
    }
    if (!empty($currentUserEntity->get('field_sas_rpps_adeli')
      ->getValue()[0]['value'])) {
      $form['field_sas_rpps_adeli']['#disabled'] = TRUE;
    }
    if ($form['field_sas_prenom'] ?? NULL) {
      $form['field_sas_prenom']['#states']['required'] = [
        ':input[id="edit-role-change-sas-effecteur"]' => ['checked' => FALSE],
      ];
    }
    if (!empty($currentUserEntity->get('field_sas_rpps_adeli')
      ->getValue()[0]['value'])) {
      unset($form['field_sas_rpps_adeli']['#states']['enabled']);
    }

  }

  /**
   * Provide some form alter for SAS user registration.
   *
   * @param array $element
   *   THe current element structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   * @param array $form
   *   THe current form structure array.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function processProfileForm(array $element, FormStateInterface $form_state, array $form) {
    $element['account']['mail']['#weight'] = -100;
    $element['account']['password_policy_status']['#access'] = FALSE;
    $element['user_' . $this->entity->id()]['#access'] = FALSE;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->setEffectorData($form_state);

    $territoires = $this->compileTerritoires($form_state);
    $form_state->setValue('field_sas_territoire', $territoires);
    parent::submitForm($form, $form_state);
  }

}
