<?php

/**
 * @file
 * Contains sas_structure_settings.page.inc.
 *
 * Page callback for Sas structure settings entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Sas structure settings templates.
 *
 * Default template: sas_structure_settings.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_sas_structure_settings(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
