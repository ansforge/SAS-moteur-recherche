<?php

namespace Drupal\sas_user\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides an entity autocomplete form element.
 *
 * The autocomplete form element allows users to select one or multiple
 * entities, which can come from all or specific bundles of an entity type.
 *
 * Properties:
 * - #default_value: (optional) The rpps/adeli of a PS.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 *  '#type' => 'rpps_adeli_user_autocomplete',
 * ];
 * @endcode
 *
 * @FormElement("rpps_adeli_user_autocomplete")
 */
class RppsAdeliUserAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = static::class;
    array_unshift($info['#process'], [$class, 'processRppsAdeliUser']);
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return NULL;
  }

  /**
   * Adds rpps/adeli autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:.
   *
   * @return array
   *   The form element.
   */
  public static function processRppsAdeliUser(array &$element) {
    $element['#autocomplete_route_name'] = 'sas_user.rpps_adeli_user';
    return $element;
  }

}
