<?php
// phpcs:ignoreFile -- PHP_CS throw an exception on JetBrains namespaces.

namespace Drupal\sas_snp\Enum;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class SnpContentConstant.
 *
 * Define constants and references data for SNP content.
 *
 * @package Drupal\sas_snp\Enum
 */
final class SnpContentConstant {

  const CNAM_PROFESSIONS_ALLOWED_TERMS_NAME = [
    'Allergologue',
    'Audioprothésiste',
    'Aide-soignant',
    'Biologiste médical',
    'Cardiologue',
    'Chirurgien-dentiste',
    'Chirurgien-Dentiste',
    'Chirurgien-dentiste spécialiste en chirurgie orale',
    'Chirurgiens-dentistes spécialiste en chirurgie orale',
    'Chirurgien-dentiste spécialiste en orthopédie dento-faciale',
    'Chirurgien-dentiste spécialiste en médecine bucco-dentaire',
    'Dentiste',
    'Dermatologue',
    'Diététicien',
    'Endocrinologue',
    'Ergothérapeute',
    'Gastro-entérologue et hépatologue',
    'Gynécologie-obstétricien',
    'Gériatre',
    'Gynécologue',
    'Gynécologue obstétricien',
    'Gynécologue-obstétricien',
    'Gynécologueobstétricien',
    'Hépato-gastro-entérologue',
    'Infirmier',
    'Infirmier psychiatrique',
    'Masseur-Kinésithérapeute',
    'Médecin',
    'Médecin généraliste',
    'Médecingénéraliste',
    'Médecin spécialiste en maladies infectieuses et tropicales',
    'Médecin vasculaire',
    'Néphrologue',
    'Neuropsychiatre',
    'Obstétricien',
    'Oto-Rhino-Laryngologue',
    'Oto-Rhino-Laryngologue (O.R.L)',
    'Oto-Rhino-Laryngologue (O.R.L) et chirurgien cervico facial',
    'Ophtalmologue',
    'Orthophoniste',
    'Orthoptiste',
    'Ostéopathe',
    'Pneumologue',
    'Pédiatre',
    'Pédicure-Podologue',
    'Sage-Femme',
    'Psychiatre',
    'Psychiatre de l\'enfant et de l\'adolescent',
    'Psychomotricien',
    'Psychologue',
    'Radiothérapeute',
    'Rhumatologue',
    'Radiologue',
    'Sage-Femme',
    'Stomatogue',
    'Stomatologue',
    'Urologue',
  ];

  const EG_TYPE_ALLOWED_TERMS_NAME = [
    'Prélèvement Covid-19',
    'Prélèvement Covid-19 - Créneaux pour personnes prioritaires',
    'Prélèvement Covid-19 - Personnes prioritaires uniquement',
    'Prélèvement Covid-19 - Temporaire',
    'SOS Médecins',
  ];

  const ROR_EXPO_TYPE_UO_ALLOWED_TERMS_NAME = [
    'Pharmacie à Usage Intérieur (PUI)',
  ];

  const ESTABLISHMENT_TYPE_ALLOWED_TERMS_NAME = [
    "Pharmacie d'Officine",
    'Centre de santé',
    'Centre de Santé',
    'Maison de santé (L.6223-3)',
    'Maison de santé (L.6223-3)',
    'Maison de santé (L6223-3)',
    'Maison de santé (L6223-3)',
  ];

  const ESTABLISHMENT_TYPE_ROR_ALLOWED_TERMS_NAME = [
    'Centre de santé',
    'Centre de Santé',
    'Maison de santé (L.6223-3)',
    'Maison de santé (L.6223-3)',
    'Maison de santé (L6223-3)',
    'Maison de santé (L6223-3)',
    'Établissement de Soins Chirurgicaux',
    'Centre hospitalier (CH)',
    'Centre hospitalier (ex Hôpital local)',
    'Centre hospitalier régional (CHR)',
  ];

  const TYPE_ETABLISSEMENT_FINESS_ALLOWED_TERMS_NAME = [
    'Centre de Santé',
    'Centre de Santé',
  ];

  const ALLOWED_ENTITY_FIELDS_IDS = [
    'professionnel_de_sante' => 'field_profession',
    'entite_geographique' => 'field_eg_type',
    'care_deals' => 'field_uo_type',
    'service_de_sante' => 'field_type_de_service_de_sante',
    'health_institution' => 'field_establishment_type',
    'finess_institution' => 'field_finess_establishment_type',
  ];

  const SAS_ALLOWED_ENTITY = [
    'sas_time_slots',
  ];

  const SAS_SNP_REF_FIELD = [
    'sas_time_slots' => 'field_sas_time_slot_ref',
  ];

  /**
   * Get allowed terms for each allowed content types.
   */
  #[ArrayShape([
    'professionnel_de_sante' => "string[]",
    'entite_geographique' => "string[]",
    'care_deals' => "string[]",
    'service_de_sante' => "string[]",
    'health_institution' => "string[]",
    'finess_institution' => "string[]",
  ])]
  public static function getTermNamesByContentTypes(): array {
    return [
      'professionnel_de_sante' => self::CNAM_PROFESSIONS_ALLOWED_TERMS_NAME,
      'entite_geographique' => self::EG_TYPE_ALLOWED_TERMS_NAME,
      'care_deals' => self::ROR_EXPO_TYPE_UO_ALLOWED_TERMS_NAME,
      'service_de_sante' => self::ESTABLISHMENT_TYPE_ALLOWED_TERMS_NAME,
      'health_institution' => self::ESTABLISHMENT_TYPE_ROR_ALLOWED_TERMS_NAME,
      'finess_institution' => self::TYPE_ETABLISSEMENT_FINESS_ALLOWED_TERMS_NAME,
    ];
  }

}
