<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\sante_user\Enum\SanteUserConstants;
use Drupal\sas_user\Enum\SasUserConstants;
use Drupal\user\Plugin\Validation\Constraint\UserMailUnique;
use Drupal\user\RegisterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the The sas user register form.
 *
 * @internal
 */
class SasRegisterForm extends RegisterForm {

  use SasUserFieldsFormTrait;
  use SasUserValidateFormTrait;
  use SasUserSubmitFormTrait;

  /**
   * Sas config manager.
   *
   * @var \Drupal\sas_config\SasApiConfigManagerInterface
   */
  protected $sasConfigManager;

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
   * Sas user service.
   *
   * @var \Drupal\sas_user\Service\SasUserHelper
   */
  protected $sasUserHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->nodeStorage = $container->get('entity_type.manager')->getStorage('node');
    $instance->termStorage = $container->get('entity_type.manager')->getStorage('taxonomy_term');
    $instance->userStorage = $container->get('entity_type.manager')->getStorage('user');
    $instance->sasTerritories = $container->get('term.territory');
    $instance->sasConfigManager = $container->get('sas_config.service');
    $instance->sasUserHelper = $container->get('sas_user.helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();
    $today = new DrupalDateTime('now', date_default_timezone_get());
    $this->entity->set('field_sas_user_sas', TRUE);
    $this->entity->set('field_sas_first_login', TRUE);
    $this->entity->set('field_password_expiration', TRUE);
    $this->entity->set('field_last_password_reset', $today->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $this->buildSasUserFieldStates($form);
    $this->buildSasUserFieldReferences($form);
    $this->buildRegisterForm($form, $form_state);

    // User role_change field for all users.
    $form['account']['roles']['#access'] = FALSE;
    $form['account']['roles']['#required'] = TRUE;

    $form['#process'][] = '::processRegisterForm';
    $form['#after_build'][] = '::afterBuildSasUserForm';
    $form['#attributes']['novalidate'] = 'novalidate';
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
  public function buildRegisterForm(array &$form, FormStateInterface $form_state) {
    $keys = ['pass', 'notify', 'status', 'name'];
    // Do not send notification mail to new account.
    $form['account']['notify']['#default_value'] = FALSE;
    foreach ($keys as $key) {
      $form['account'][$key]['#access'] = FALSE;
    }

    $form['account']['mail']['#description'] = $this->t(
    // phpcs:ignore
      "Une adresse électronique valide. Le système enverra tous les courriels à cette adresse. L'adresse électronique ne sera pas rendue publique et ne sera utilisée que pour la réinitialisation du mot de passe." //NOSONAR
    );
    $form['account']['roles']['#description'] = $this->t("Veuillez choisir le(s) rôle(s) pour l'utilisateur.");

    if (isset($form['role_change'])) {
      $form['role_change']['widget']['#description'] = $this->t("Veuillez choisir le(s) rôle(s) pour l'utilisateur.");
      $form['role_change']['widget']['#required'] = TRUE;
      $cgu_config = $this->sasConfigManager->getConfigByGroup('cgu')[0];
      $notice_register = !empty($cgu_config) && !empty($cgu_config['value']['notice_osnp_ioa']['value'])
        ? '<div id="notice-osnp-ioa" class="hidden">' . $cgu_config['value']['notice_osnp_ioa']['value'] . '</div>'
        : '';
      $form['role_change']['#suffix'] = $notice_register;
    }

    $form['account']['mail']['#required'] = TRUE;
    $form['account']['name']['#value'] = uniqid('sas_user_');
    $form['account']['name']['#type'] = 'hidden';
    $form['field_sas_territoire']['widget']['#chosen'] = TRUE;
    if (isset($form['field_sas_territoire']['widget']['#options']['_none'])) {
      unset($form['field_sas_territoire']['widget']['#options']['_none']);
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
  public function processRegisterForm(array $element, FormStateInterface $form_state, array $form) {
    $element['account']['mail']['#weight'] = -100;
    $element['account']['password_policy_status']['#access'] = FALSE;
    $element['user_']['#access'] = FALSE;
    // Generate a valid password according to password_policy constraints.
    $password = \Drupal::service('sante_user.password_generator')->generate();
    $element['account']['pass']['#value']['pass1'] = $password;
    $element['account']['pass']['#value']['pass2'] = $password;
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo We should redirect to a special user edit confirmation form like node_preview.
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // If User email exists is the only error and user has not santé administrators roles.
    // Then update santé loaded user.
    if ($violations->count() === 1
      && $violations->getByField('mail')->count() === 1
      && $violations->getByField('mail')->get(0)->getConstraint() instanceof UserMailUnique) {
      /** @var \Drupal\user\UserInterface $user */
      $user = user_load_by_mail($form_state->getValue('mail'));

      $currentUserRoles = $this->currentUser()?->getRoles(TRUE) ?? [];
      $user_is_sante_admin = !empty(array_intersect(SanteUserConstants::SANTE_ADMIN_ROLES, $currentUserRoles));
      $user_is_sas_admin = !empty(array_intersect(SasUserConstants::SAS_ADMIN_USER_ROLES, $currentUserRoles));

      if (!$user_is_sante_admin
        && $user_is_sas_admin
        && $user->hasField('field_sas_user_sas')
        && ($user->get('field_sas_user_sas')->isEmpty() || empty($user->get('field_sas_user_sas')->value))) {

        $form_state->setValue('name', $user->get('name')->getValue());
        $values = $form_state->getValues();
        // Propagate Santé roles if any.
        $values['roles'] = $user->getRoles();
        // Don't update Santé user name.
        $values['name'] = $user->get('name')->value;
        // Don't reset already set password.
        unset($values['pass']);
        $form_state->setValues($values);
        // Set the current edited entity as the loaded user.
        $this->setEntity($user);
        $violations->filterByFields(['mail'])->remove(0);
      }
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->setEffectorData($form_state);

    $territoires = $this->compileTerritoires($form_state);
    $form_state->setValue('field_sas_territoire', $territoires);
    parent::submitForm($form, $form_state);
    $departement = $form_state->getValue('field_sas_departement', [])[0]['target_id'] ?? NULL;
    if (empty($departement)) {
      $ville = current($this->entity->get('field_ville')->referencedEntities());
      if ($ville && !$ville->get('field_department')->isEmpty()) {
        $this->entity->set('field_sas_departement', $ville->get('field_department')->first()->entity);
      }
    }
    $form_state->setRedirect('sas_user.admin_create');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->isNew()) {
      $return = parent::save($form, $form_state);
    }
    else {
      // If updating a santé user.
      $account = $this->entity;
      $return = $account->save();

      $form_state->set('user', $account);
      $form_state->setValue('uid', $account->id());

      $this->logger('user')->notice('Santé user updated with SAS credentials: %name %email.', [
        '%name' => $account->getAccountName(),
        '%email' => '<' . $account->getEmail() . '>',
        'type' => $account->toLink($this->t('Edit'), 'sas-edit')->toString(),
      ]);
      if (_user_mail_notify('register_admin_created', $account)) {
        $this->messenger()->addStatus($this->t('A welcome message with further instructions has been emailed to the new user <a href=":url">%name</a>.', [
          ':url' => $account->toUrl('sas-edit')->toString(),
          '%name' => $account->getAccountName(),
        ]));
      }
    }

    return $return;
  }

}
