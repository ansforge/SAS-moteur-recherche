<?php

namespace Drupal\sas_structure\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'sos_medecin_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "sos_medecin_autocomplete",
 *   label = @Translation("SOS Médecin autocomplete"),
 *   field_types = {"string"},
 * )
 */
class SosMedecinAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['value'] = $element + [
      '#type' => 'sos_medecin_autocomplete',
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    return $element;
  }

}
