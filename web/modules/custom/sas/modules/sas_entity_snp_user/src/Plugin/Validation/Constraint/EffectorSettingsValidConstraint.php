<?php

namespace Drupal\sas_entity_snp_user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if effector settings are valid.
 *
 * @Constraint(
 *   id = "EffectorSettingsValid",
 *   label = @Translation("Effector Settings Valid Constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class EffectorSettingsValidConstraint extends Constraint {

  /**
   * The message that will be shown if SAS Participation Via is not provided.
   *
   * @var string
   */
  public string $emptySasParticipationVia = "Missing SAS participation method.";

  /**
   * The message that will be shown if effector is participating to SAS via a CPTS, without indicating on which places.
   *
   * @var string
   */
  public string $emptyCptsLocations = "Veuillez indiquer au moins un lieu d'activité.";

  /**
   * The message that will be shown if effector is participating to SAS via a MSP or CPTS, without indicating the structure.
   *
   * @var string
   */
  public string $emptyFiness = "Missing FINESS number.";

  /**
   * The message that will be shown if effector is participating to SAS via a MSP or CPTS, and giving invalid FINESS.
   *
   * @var string
   */
  public string $invalidFiness = "Invalid FINESS number format.";

  /**
   * The message that will be shown if effector is participating to SAS via a MSP or CPTS, and giving not existing FINESS.
   *
   * @var string
   */
  public string $notExistingFiness = "Not existing FINESS number.";

  /**
   * The message that will be shown if effector has not indicated if it has software editors or not.
   *
   * @var string
   */
  public string $emptyHasSoftware = "Veuillez indiquer si vous disposez d'un logiciel de rendez-vous.";

  /**
   * The message that will be shown if effector has not declared on honor availabilities for SAS.
   *
   * @var string
   */
  public string $invalidHoursAvailable = "Veuillez déclarer sur l'honneur la mise à disposition de disponibilités pour le SAS.";

}
