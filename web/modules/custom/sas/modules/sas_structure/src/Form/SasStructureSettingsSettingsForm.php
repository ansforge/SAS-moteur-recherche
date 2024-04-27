<?php

namespace Drupal\sas_structure\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SasStructureSettingsSettingsForm.
 *
 * Form class to manage settings of SasStructureSettings entity.
 *
 * @ingroup sas_structure
 */
class SasStructureSettingsSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'sasstructuresettings_settings';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('The configuration has been updated.'));
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sasstructuresettings_settings']['#markup'] = 'Settings form for Sas structure settings entities. Manage field settings here.';

    $form['settings'] = [
      '#markup' => $this->t('Settings form for Sas structure settings entities. Manage field settings here.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

}
