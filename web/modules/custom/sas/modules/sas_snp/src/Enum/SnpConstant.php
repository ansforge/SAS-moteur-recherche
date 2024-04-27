<?php

namespace Drupal\sas_snp\Enum;

/**
 * Define ImporterItem status.
 */
final class SnpConstant {

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_administrateur
   */
  const SAS_ADMINISTRATEUR = 'sas_administrateur';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_administrateur_national
   */
  const SAS_ADMINISTRATEUR_NATIONAL = 'sas_administrateur_national';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_regulateur_osnp
   */
  const SAS_REGULATEUR_OSNP = 'sas_regulateur_osnp';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_ioa
   */
  const SAS_IOA = 'sas_ioa';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_effecteur
   */
  const SAS_EFFECTEUR = 'sas_effecteur';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_gestionnaire_de_structure
   */
  const SAS_GESTIONNAIRE_STRUCTURE = 'sas_gestionnaire_de_structure';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_gestionnaire_de_structure
   */
  const SAS_GESTIONNAIRE_DE_COMPTES = 'sas_gestionnaire_de_comptes';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_delegataire
   */
  const SAS_DELEGATAIRE = 'sas_delegataire';

  /**
   * DO NOT USE THIS CONSTANT. Use SasUserConstant::SAS_ADMIN_ROLE.
   *
   * @const string sas_delegataire
   */
  const SAS_REFERENT_TERRITORIAL = 'sas_referent_territorial';

  /**
   * @const string health_institution
   */
  const HEALTH_INSTITUTION = 'health_institution';

  /**
   * @const string finess_institution
   */
  const FINESS_INSTITUTION = 'finess_institution';

  /**
   * @const string service_de_sante
   */
  const SERVICE_SANTE = 'service_de_sante';

  /**
   * @const string professionnel_de_sante
   */
  const PROFESSIONNEL_SANTE = 'professionnel_de_sante';

  /**
   * Content type id for "Entité géographique" content.
   *
   * @const string entite_geographique
   */
  const GEOGRAPHIC_ENTITY = 'entite_geographique';

  /**
   * @const string professionnel_de_sante
   */
  const SAS_PROFESSIONAL_CT = 'professionnel_de_sante';

  /**
   * @const string sas_time_slots
   */
  const SAS_TIME_SLOTS = 'sas_time_slots';

  /**
   * @const int sas_max_vacation_slot_nb
   */
  const SAS_MAX_VACATION_SLOT_NB = 3;

  /**
   * @const array sas_structure_id_field_mapping
   */
  const SAS_STRUCTURE_ID_FIELDS_MAPPING = [
    'field_identifiant_active_rpps' => 'rpps_rang',
    'field_identifiant_finess' => 'finess',
    'field_identifiant_str_finess' => 'finess',
    'field_identif_siret' => 'siret',
    'field_ident_service_sante_ror' => 'ror',
    'field_identifiant_personne_ror' => 'ror',
  ];

  /**
   * @const array sas_modalities
   */
  const SAS_MODALITIES = [
    'home',
    'teleconsultation',
    'physical',
  ];

  /**
   * @const array sas_slot_type
   */
  const SAS_SLOT_TYPE = [
    'dated',
    'recurring',
  ];

  /**
   * Get the status items to process.
   *
   * @return string[]
   *   The status array.
   */
  public static function getSasBunles() {
    return [
      self::HEALTH_INSTITUTION,
      self::FINESS_INSTITUTION,
      self::SERVICE_SANTE,
      self::PROFESSIONNEL_SANTE,
      self::GEOGRAPHIC_ENTITY,
    ];
  }

}
