<?php

namespace Drupal\sas_structure\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'cpts_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "cpts_autocomplete",
 *   label = @Translation("CPTS autocomplete"),
 *   field_types = {"string"},
 * )
 */
class CptsAutocompleteWidget extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'cpts_autocomplete',
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    return $element;
  }

}
