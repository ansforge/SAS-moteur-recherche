<?php

namespace Drupal\sas_structure\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\sas_structure\Enum\StructureConstant;

/**
 * Provides an entity autocomplete form element.
 *
 * The autocomplete form element allows users to select one or multiple
 * entities, which can come from all or specific bundles of an entity type.
 *
 * Properties:
 * - #default_value: (optional) The default finess of CPTS.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 *  '#type' => 'cpts_autocomplete',
 * ];
 * @endcode
 *
 * @FormElement("cpts_autocomplete")
 */
class CptsAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = static::class;

    $info['#element_validate'] = [[$class, 'validateCptsAutocomplete']];
    array_unshift($info['#process'], [$class, 'processCptsAutocomplete']);

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
      return static::getCptsAutocompleteLabel($element['#default_value']);
    }

    return NULL;
  }

  /**
   * Adds CPTS autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used.
   *
   * @return array
   *   The form element.
   */
  public static function processCptsAutocomplete(array &$element) {

    $element['#autocomplete_route_name'] = 'sas_structure.cpts_autocomplete';

    return $element;
  }

  /**
   * Form element validation handler for cpts_autocomplete elements.
   */
  public static function validateCptsAutocomplete(array &$element, FormStateInterface $form_state) {
    $finess = NULL;

    if (!empty($element['#value'])) {
      $finess = static::extractCptsFinessFromAutocompleteInput($element['#value']);

      if (empty($finess)) {
        $form_state->setError(
          $element,
          "Impossible de retrouver le numéro finess. Veuillez sélectionner un élément dans la liste."
        );
      }
      elseif (!static::isCptsValid($finess)) {
        $form_state->setError(
          $element,
          sprintf(
            "Le numéro finess %s ne correspond pas à une CPTS. Veuiller sélectionner un élément dans la liste.",
            $finess
          )
        );

      }
    }

    $form_state->setValueForElement($element, $finess);
  }

  /**
   * Checks if a structure identified by its FINESS code is of type 'CPTS'.
   *
   * @param mixed $finess
   *   The FINESS code of the structure to be verified.
   *
   * @return bool
   *   Returns TRUE if the structure is a 'CPTS', FALSE otherwise.
   */
  protected static function isCptsValid(mixed $finess) {
    $node = \Drupal::service('sas_structure.finess_structure_helper')
      ->getStructureByFiness(
        $finess,
        StructureConstant::CONTENT_TYPE_HEALTH_INSTITUTION
      );

    if (empty($node)) {
      return FALSE;
    }

    return \Drupal::service('sas_structure.helper')->isCpts($node);
  }

  /**
   * Extracts CPTS finess from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractCptsFinessFromAutocompleteInput($input) {
    $match = NULL;

    if (preg_match("/.+\s\((\d+)\)/u", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

  /**
   * Get CPTS label to display in autocomplete field.
   *
   * @param string $finess
   *   Finess number.
   *
   * @return string
   *   CPTS Label.
   */
  public static function getCptsAutocompleteLabel(string $finess): string {
    $node = \Drupal::service('sas_structure.finess_structure_helper')->getStructureByFiness($finess, StructureConstant::CONTENT_TYPE_HEALTH_INSTITUTION);

    return sprintf(
      '%s (%s)',
      !empty($node) ? $node->getTitle() : t('!! CPTS non trouvée !!'),
      $finess
    );
  }

}
