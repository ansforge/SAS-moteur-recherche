<?php

namespace Drupal\sas_user\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserCancelForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class override User cancel form.
 */
class SasUserCancelForm extends UserCancelForm {

  /**
   * The sas_core service.
   *
   * @var \Drupal\sas_core\SasCoreServiceInterface
   */
  protected $sasCoreService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->sasCoreService = $container->get('sas_core.service');

    return $instance;

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->sasCoreService->isSasContext()) {
      // Hide email confirm checkbox.
      if (isset($form['user_cancel_confirm'])) {
        $form['user_cancel_confirm']['#access'] = FALSE;
      }

      // Set default cancel method to delete.
      if (isset($form['user_cancel_method'])) {
        $form['user_cancel_method']['#default_value'] = 'user_cancel_delete';
      }
    }

    return $form;
  }

}
