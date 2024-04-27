<?php

namespace Drupal\sas_user\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Defines the 'sas_user_territory_autocomplete' field widget.
 *
 * @FieldWidget(
 *   id = "sas_user_territory_content_autocomplete",
 *   label = @Translation("Sas User EntityReference Node with territory Autocomplete"),
 *   description = @Translation("Sas User EntityReference Node with territory autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SasUserTerritoryContentAutocomplete extends SasUserTerritoryAutocompleteBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($form_state->get('territoire-' . $this->fieldDefinition->getName())) {
      $territoire = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($form_state->get('territoire-' . $this->fieldDefinition->getName()));
      if ($territoire instanceof TermInterface && $territoire->bundle() === 'sas_territoire') {
        $cp = str_replace(',', '+', $territoire->get('field_sas_postal_codes')->value);
        $element['target_id']['#selection_settings']['view']['arguments']['field_address_postal_code'] = $cp;
      }
    }

    return $element;
  }

}
