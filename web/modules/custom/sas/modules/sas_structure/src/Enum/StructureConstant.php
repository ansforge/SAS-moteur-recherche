<?php

namespace Drupal\sas_structure\Enum;

/**
 * Class StructureConstant.
 *
 * Provide global structure constants.
 *
 * @package Drupal\sas_structure\Enum
 */
final class StructureConstant {

  /**
   * MSP structure type id for internal use.
   */
  const STRUCTURE_TYPE_MSP = 'msp';

  /**
   * CDS structure type id for internal use.
   */
  const STRUCTURE_TYPE_CDS = 'cds';

  /**
   * CPTS structure type id for internal use.
   */
  const STRUCTURE_TYPE_CPTS = 'cpts';

  /**
   * SOS Medecin structure type id for internal use.
   */
  const STRUCTURE_TYPE_SOS_MEDECIN = 'sos';

  /**
   * FINESS Structure ID type.
   */
  const ID_TYPE_FINESS = 'finess';

  /**
   * FINESS Structure ID type.
   */
  const ID_TYPE_SIRET = 'siret';

  /**
   * Get structure id types.
   *
   * @return string[]
   */
  public static function getIdTypes(): array {
    return [
      self::ID_TYPE_SIRET,
      self::ID_TYPE_FINESS,
    ];
  }

  /**
   * Get structure types.
   *
   * @return string[]
   */
  public static function getStructureTypes(): array {
    return [
      self::STRUCTURE_TYPE_CPTS,
      self::STRUCTURE_TYPE_MSP,
      self::STRUCTURE_TYPE_SOS_MEDECIN,
    ];
  }

  /**
   * List of vocabularies used to set type of structure.
   */
  const STRUCTURE_TYPE_TAXONOMIES = [
    'establishment_type_ror',
    'type_etablissement_finess',
    'establishment_type',
  ];

  const CONTENT_TYPE_HEALTH_INSTITUTION = 'health_institution';

  const CONTENT_TYPE_FINESS_INSTITUTION = 'finess_institution';

  const CONTENT_TYPE_HEALTH_SERVICE = 'service_de_sante';

  /**
   * List of content type names where MSP structure can be store.
   */
  const STRUCTURE_CONTENT_TYPES = [
    'service_de_sante',
    'health_institution',
    'finess_institution',
  ];

  /**
   * List of content type names where CDS structure can be store.
   */
  const STRUCTURE_CDS_CONTENT_TYPES = [
    'service_de_sante',
    'health_institution',
    'finess_institution',
  ];

  /**
   * List of content type names where structure can be store.
   */
  const STRUCTURE_MSP_CONTENT_TYPES = [
    'service_de_sante',
    'health_institution',
    'finess_institution',
  ];

  /**
   * Field list where structure type is set for CDS.
   */
  const STRUCTURE_CDS_FIELDS = [
    'field_type_de_service_de_sante',
    'field_establishment_type',
    'field_finess_establishment_type',
  ];

  /**
   * Field list where structure type is set for MSP.
   */
  const STRUCTURE_MSP_FIELDS = [
    'field_type_de_service_de_sante',
    'field_establishment_type',
  ];

  /**
   * Field list where structure type is set for MSP.
   */
  const STRUCTURE_CPTS_FIELDS = [
    'field_establishment_type',
  ];

  /**
   * List of taxonomy term names to identify "Maison de santé" (MSP).
   */
  const STRUCTURE_MSP_TERMS = [
    'Maison de santé (L6223-3)',
    'Maison de santé (L.6223-3)',
  ];

  /**
   * List of taxonomy term names to identify "Centre de santé" (CDS).
   */
  const STRUCTURE_CDS_TERMS = [
    'Centre de Santé',
    'Centre de santé',
  ];

  /**
   * Taxonomy term to identify CPTS.
   */
  const STRUCTURE_CPTS_TERM = [
    'Communauté Professionnelle Territoriale de Santé (CPTS)',
  ];

  /**
   * Content type where is stored SOS Médecin data.
   */
  const SOS_MEDECIN_CONTENT_TYPE = 'entite_geographique';

  /**
   * Content type where is stored CPTS data.
   */
  const CPTS_CONTENT_TYPE = [
    'health_institution',
  ];

  /**
   * Field name where association name is stored.
   */
  const SOS_MEDECIN_ASSOCIATION_NAME_FIELD = 'field_precision_type_eg';

  /**
   * Vocabulary name use to set type to "entité géographique" (EG).
   */
  const SOS_MEDECIN_VOCABULARY = 'eg_type';

  /**
   * Vocabulary name use to set type to "ROR Catégorie d'établissement".
   */
  const CPTS_VOCABULARY = 'establishment_type_ror';

  /**
   * Taxonomy term name corresponding to SOS Médecin EG type.
   */
  const SOS_MEDECIN_TERM = 'SOS Médecins';

  const SOS_MEDECIN_USER_FIELD_NAME = 'field_sas_sos_medecin_assos';

  const CPTS_USER_FIELD_NAME = 'field_sas_cpts';

  const CDS_USER_FIELD_NAME = 'field_sas_attach_structures';

}
