<?php

namespace Drupal\sas_structure\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Sas structure settings edit forms.
 *
 * @ingroup sas_structure
 */
class SasStructureSettingsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Sas structure settings.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Sas structure settings.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.sas_structure_settings.canonical', ['sas_structure_settings' => $entity->id()]);
  }

}
