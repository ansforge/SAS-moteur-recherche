<?php

namespace Drupal\sas_structure\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides an entity autocomplete form element.
 *
 * The autocomplete form element allows users to select one or multiple
 * entities, which can come from all or specific bundles of an entity type.
 *
 * Properties:
 * - #default_value: (optional) The default siret of SOS Médecin association.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 *  '#type' => 'sos_medecin_autocomplete',
 * ];
 * @endcode
 *
 * @FormElement("sos_medecin_autocomplete")
 */
class SosMedecinAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = static::class;

    $info['#element_validate'] = [[$class, 'validateSosMedecinAutocomplete']];
    array_unshift($info['#process'], [$class, 'processSosMedecinAutocomplete']);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      // This should be a string, but allow other scalars since they might be
      // valid input in programmatic form submissions.
      if (!is_scalar($input)) {
        $input = '';
      }
      return $input;
    }
    elseif (!empty($element['#default_value'])) {
      return static::getAssociationAutocompleteLabel($element['#default_value']);
    }
    return NULL;
  }

  /**
   * Adds SOS Medecin autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used.
   *
   * @return array
   *   The form element.
   */
  public static function processSosMedecinAutocomplete(array &$element) {

    $element['#autocomplete_route_name'] = 'sas_structure.sos_medecin.association_autocomplete';

    return $element;
  }

  /**
   * Form element validation handler for sas_medecin_autocomplete elements.
   */
  public static function validateSosMedecinAutocomplete(array &$element, FormStateInterface $form_state) {
    $siret = NULL;

    if (!empty($element['#value'])) {
      $siret = static::extractAssociationSiretFromAutocompleteInput($element['#value']);

      if (empty($siret)) {
        $form_state->setError(
          $element,
          "Impossible de retrouver le numéro siret. Veuillez sélectionner un élément dans la liste."
        );
      }
      elseif (!\Drupal::service('sas_structure.sos_medecin')->isSosMedecinAssociation($siret)) {
        $form_state->setError(
          $element,
          sprintf(
            "Le numéro siret %s ne correspond pas à une association SOS Médecin. Veuiller sélectionner un élément dans la liste.",
            $siret
          )
        );

      }
    }

    $form_state->setValueForElement($element, $siret);
  }

  /**
   * Get SOS Médecin association label to display in autocomplete field.
   *
   * @param string $siret
   *   Association siret.
   *
   * @return string
   *   Association Label.
   */
  public static function getAssociationAutocompleteLabel(string $siret) {
    $association_name = \Drupal::service('sas_structure.sos_medecin')->getAssociationNameBySiret($siret);

    return sprintf(
      '%s (%s)',
      $association_name ?? 'Association name not found',
      $siret
    );
  }

  /**
   * Extracts SOS Medecin Association siret from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractAssociationSiretFromAutocompleteInput($input) {
    $match = NULL;

    if (preg_match("/.+\s\(([0-9]+)\)/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

}
