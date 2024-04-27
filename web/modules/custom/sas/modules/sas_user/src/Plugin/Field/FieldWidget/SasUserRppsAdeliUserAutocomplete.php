<?php

namespace Drupal\sas_user\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'rpps_adeli_user_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "rpps_adeli_user_autocomplete",
 *   label = @Translation("RPPS/ADELI Autocomplete"),
 *   field_types = {"string"},
 * )
 */
class SasUserRppsAdeliUserAutocomplete extends widgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'rpps_adeli_user_autocomplete',
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    return $element;
  }

}
