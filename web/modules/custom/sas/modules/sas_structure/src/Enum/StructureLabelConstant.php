<?php

namespace Drupal\sas_structure\Enum;

/**
 * Class StructureLabelConstant.
 *
 * Provide constant for labels relatives to structure.
 *
 * @package Drupal\sas_structure\Enum
 */
final class StructureLabelConstant {

  /**
   * Popin main section title.
   */
  const FORM_MAIN_SECTION_TITLE = "Je participe au SAS en tant que Centre de Santé";
  /**
   * Popin main section title.
   */
  const FORM_MAIN_SECTION_TITLE_SOS_MEDECIN = "Je participe au SAS en tant qu'association SOS Médecins";

  /**
   * Practioner count field label.
   */
  const FORM_PRACTITIONER_COUNT = "Nombre de professionnel de santé du Centre de santé participant au SAS";

  /**
   * Practitioner count warning message.
   */
  const FORM_PRACTITIONER_COUNT_MESSAGE = <<<eof
Attention afin d'enregistrer votre participation au SAS, vous devez à minima déclarer un professionnel de santé
eof;

  /**
   * Sas participation checkbox label.
   */
  const FORM_SAS_PARTICIPATION_CHECKBOX = <<<eof
J'accepte d'être directement contacté par la régulation afin que le Centre soit sollicité
pour prendre des patients en sus de ses disponibilités
eof;

  /**
   * Hour declaration checkbox label.
   */
  const FORM_HOUR_DECLARATION_CHECKBOX = <<<eof
Je déclare sur l'honneur mettre en visibilité à minima 2 heures de disponibilité par semaine
pour chaque médecin généraliste de mon centre de santé sur mon agenda de la plateforme numérique SAS
eof;

  /**
   * Hour declaration checkbox label for SOS medecin.
   */
  const FORM_SOS_MEDECIN_HOUR_DECLARATION_CHECKBOX = <<<eof
Je déclare sur l'honneur mettre en visibilité à minima 2 heures de disponibilité par semaine pour chaque médecin généraliste
de mon association SOS Médecins sur mon agenda de la plateforme numérique SAS
eof;

  /**
   * Error message if structure id is missing.
   */
  const FORM_ERROR_MISSING_STRUCTURE_ID = <<<eof
Impossible d'enregistrer les informations. Identifiant de structure manquant (FINESS ou SIRET).
Veuillez contacter un administrateur.
eof;

  /**
   * Error message for hours declaration checkbox not checked.
   */
  const FORM_ERROR_HOUR_DECLARATION_REQUIRED = "Veuillez déclarer sur l'honneur la mise à disposition de disponibilités pour le SAS.";

  /**
   * Cancel button text.
   */
  const FORM_CANCEL = "Annuler";

  /**
   * Save button text.
   */
  const FORM_SAVE = "Enregistrer";

}
