<?php

namespace Drupal\sas_structure\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if structure settings are valid.
 *
 * @Constraint(
 *   id = "StructureSettingsValid",
 *   label = @Translation("Structure Settings Valid Constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class StructureSettingsConstraint extends Constraint {

  /**
   * The message that will be shown if effector has not declared on honor availabilities for SAS.
   *
   * @var string
   */
  public string $invalidHoursAvailable = "Veuillez déclarer sur l'honneur la mise à disposition de disponibilités pour le SAS.";

  /**
   * The message that will be shown if effector has not declared on honor availabilities for SAS.
   *
   * @var string
   */
  public string $invalidPractitionerCount = "Veuillez préciser le nombre d'effecteur pour la structure.";

}
