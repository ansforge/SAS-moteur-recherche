<?php

namespace Drupal\sas_user\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'sas_user_territory_user_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "sas_user_territory_user_autocomplete",
 *   label = @Translation("Sas User EntityReference User with territory Autocomplete"),
 *   description = @Translation("Sas User EntityReference User with territory autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SasUserTerritoryUserAutocomplete extends SasUserTerritoryAutocompleteBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $value = $form_state->get('territoire-' . $this->fieldDefinition->getName());
    if ($value) {
      $element['target_id']['#selection_settings']['view']['arguments']['field_sas_territoire_target_id'] = $value;
    }

    return $element;
  }

}
