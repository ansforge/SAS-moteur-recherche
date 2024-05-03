<?php

namespace Drupal\sas_search\Enum;

/**
 * Class SasSolrQueryConstant.
 *
 * Provides constant for solr query.
 *
 * @package Drupal\sas_search\Enum
 */
final class SasSolrQueryConstant {

  const SOLR_INDEX = 'health_offer';

  const DEFAULT_PAGE = 1;
  const DEFAULT_ITEM_PER_PAGE = 25;
  const SEARCH_TYPE_DEFAULT = 'normal';
  const SEARCH_TYPE_EMERGENCY = 'emergency';
  const SEARCH_TYPE_MATERNITY = 'maternity';

  const SEARCH_SORT_DISTANCE = 'distance';

  const SEARCH_SORT_RANDOM = 'random';

  const BASE_QUERY_PARAMETERS = [
    'lowercaseOperators=false',
    'defType=edismax',
    'sow=false',
    'ps=10',
    'wt=json',
    'json.nl=map',
    'q.op=AND',
  ];

  const OPENED_HOURS = [
    0 => ['00', '01', '02', '03', '04', '05'],
    1 => ['06', '07', '08', '09', '10', '11'],
    2 => ['12', '13', '14', '15', '16', '17'],
    3 => ['18', '19', '20', '21', '22', '23'],
  ];

  const FIELD_BASE_LIST = [
    'id',
    'its_nid',
    'index_id',
    'score',
    'ds_changed',
    'ds_date_common_for_sort',
    'ss_type',
    'ss_field_site_internet',
    'ss_field_site_internet_title',
    'ss_etb_title',
    'tm_X3b_und_field_service_type_r213_etq',
    'bs_field_urgences',
    'ss_field_departement_code',
    'sm_field_custom_label_permanent_label',
    'sm_field_custom_label_temporaire_label',
    'tm_X3b_und_spec_tags',
    'tm_X3b_und_convention_type',
    'itm_convention_type_number',
    'tm_X3b_und_field_uo_type_name',
    'ss_field_custom_group',
    'tm_X3b_und_field_nom',
    'tm_X3b_und_field_prenom',
    'tm_X3b_und_field_profession_name',
    'ss_field_address',
    'ss_field_street',
    'ss_field_codepostal',
    'tm_X3b_und_field_ville',
    'tm_X3b_und_field_department',
    'ss_field_department_code',
    'tm_X3b_und_field_region',
    'ss_field_region_code',
    'tm_X3b_und_etb_telephones',
    'tm_X3b_und_field_specialite_name',
    'ss_etb_item_id',
    'ss_etb_title',
    'tm_X3b_und_etb_title',
    'ss_etb_address',
    'ss_etb_path_alias',
    'tm_X3b_und_etb_telephones',
    'tm_X3b_und_establishment_type_names',
    'itm_establishment_types',
    'itm_field_add_offre_labels',
    'sm_field_horaires',
    'its_field_profession',
    'ss_field_profession_name',
    'ss_field_node_path_alias',
    'tm_X3b_und_title',
    'sm_os_type_prise_charge_ref',
    'tm_X3b_und_field_phone_number',
    'sm_field_maternite_level',
    'ss_field_maternite_level_label',
    'tm_X3b_und_field_precision_type_eg',
    'ds_field_eg_date_ouverture',
    'ds_field_eg_date_fermeture',
    'locs_field_geolocalisation_latlon',
    'dist:geodist()',
  ];

  const FIELD_SAS_LIST = [
    'ss_field_identifiant_rpps',
    'ss_field_personne_adeli_num',
    'ss_field_identif_siret',
    'ss_field_identifiant_active_rpps',
    'ss_field_identifiant_str_finess',
    'ss_field_ident_service_sante_ror',
    'ss_field_identifiant_finess',
    'sm_sas_territory_labels',
    'sm_sas_territory_ids',
    'sm_sas_intervention_zone_insee',
    'sm_sas_cpts_care_deal_phones',
    'bs_sas_overbooking',
    'bs_sas_forfait_reo',
    'bs_sas_editor_disabled',
    'bs_sas_is_interfaced',
    'ss_sas_additional_info',
    'ss_sas_cpts_finess',
    'ss_sas_cpts_label',
    'sm_sas_cpts_phone',
    'bs_sas_participation',
    'its_sas_participation_via',
    'ss_field_identifiant',
    'ss_sas_timezone',
  ];

  const FIELD_TAGS_LIST = [
    'tm_X3b_und_field_tags_pos_health_center_name',
    'tm_X3b_und_field_service_type_r213_etq',
    'sm_field_uo_type_name',
    'tm_X3b_und_field_categorie_organisation_r244_etq',
    'ss_field_categorie_organisation_label',
    'ss_field_temporalite_accueil_label',
    'tm_X3b_und_field_temporalite_accueil_label_text',
    'ss_field_public_pris_en_charge_label',
    'tm_X3b_und_field_public_pris_en_charge_label_text',
    'sm_field_types_patients',
    'tm_X3b_und_field_type_etb_r66_etq',
    'tm_X3b_und_field_type_etb_r66_synonyme',
  ];

  const FACET_FIELD_LIST = [
    'itm_establishment_types',
    'itm_convention_type_number',
    'itm_field_maternite_level_tid',
  ];

  const FIELD_BOOST = [
    'tm_X3b_und_additional_text' => '1.0',
    'sm_field_ages' => '0.1',
    'tm_X3b_und_convention_type' => '1.0',
    'tm_X3b_und_establishment_type_names' => '1.0',
    'tm_X3b_und_field_actives_specifiques_name' => '1.0',
    'tm_X3b_und_field_custom_label_permanent' => '2.0',
    'sm_field_custom_label_temporaire_label' => '2.0',
    'sm_field_custom_label_permanent_label' => '2.0',
    'tm_X3b_und_field_types_uo_aptitudes_name' => '1.0',
    'tm_X3b_und_field_complementary_info' => '1.0',
    'tm_X3b_und_field_custom_etiquette_etab' => '1.0',
    'tm_X3b_und_field_department' => '0.5',
    'sm_field_parent_eg_eg_type_eg_tags_name' => '1.0',
    'tm_X3b_und_field_finess_equipement_name' => '1.0',
    'tm_X3b_und_field_finess_equipement_tags_name' => '1.0',
    'tm_X3b_und_field_finess_equipement_translation' => '1.0',
    'tm_X3b_und_field_finess_establishment_type_description' => '1.0',
    'tm_X3b_und_field_finess_establishment_type_finess_etiquette_name' => '1.0',
    'tm_X3b_und_field_finess_establishment_type_finess_traduction' => '1.0',
    'sm_field_type_etb_r66_etq' => '1.0',
    'tm_X3b_und_field_type_etb_r66_synonyme' => '1.0',
    'sm_field_keywords_name' => '0.5',
    'sm_field_langues_parlees_name' => '0.1',
    'tm_X3b_und_field_nom' => '0.5',
    'tm_X3b_und_field_prenom' => '0.5',
    'tm_X3b_und_field_precision_type_eg' => '3.0',
    'tm_X3b_und_field_presentation' => '1.0',
    'tm_X3b_und_field_presentation_depistage' => '1.0',
    'tm_X3b_und_field_presentation_vaccination' => '1.0',
    'tm_X3b_und_field_profession_name' => '2.0',
    'tm_X3b_und_field_region' => '0.5',
    'tm_X3b_und_field_service_type_r213_etq' => '1.0',
    'tm_X3b_und_field_tags_descendants' => '1.0',
    'tm_X3b_und_field_tags_field_pos_health_center_descendants' => '1.0',
    'tm_X3b_und_field_tags_pos_health_center_name' => '1.0',
    'tm_X3b_und_field_tags_name' => '1.0',
    'tm_X3b_und_field_uo_type_r211_etq' => '1.0',
    'tm_X3b_und_field_categorie_organisation_r244_etq' => '1.0',
    'tm_X3b_und_field_categorie_organisation_label_text' => '1.0',
    'tm_X3b_und_field_temporalite_accueil_label_text' => '1.0',
    'tm_X3b_und_field_public_pris_en_charge_label_text' => '1.0',
    'tm_X3b_und_field_types_patients_text' => '1.0',
    'tm_X3b_und_field_tags_eg_name' => '1.0',
    'tm_X3b_und_field_telephone_fixe' => '1.0',
    'ss_field_telephone_portable' => '1.0',
    'tm_X3b_und_uo_type_support_type_ref_name' => '2.0',
    'tm_X3b_und_field_uo_type_name' => '2.0',
    'tm_X3b_und_uo_type_support_type_label' => '2.0',
    'tm_X3b_und_field_ville' => '0.5',
    'tm_X3b_und_etb_title' => '0.5',
    'tm_X3b_und_field_information_type_names' => '1.0',
    'tm_X3b_und_field_specialite_name' => '1.0',
    'tm_prof_specialities$referring_pivot_term_names' => '1.0',
    'tm_X3b_und_spec_tags' => '1.0',
    'tm_X3b_und_tags_ao_mpec' => '1.0',
    'tm_X3b_und_title' => '5.0',
    'ss_field_identifiant_rpps' => '1.0',
    'tm_X3b_und_field_phone_number' => '1.0',
  ];

  const PHRASE_FIELD_BOOST = [
    'tm_X3b_und_additional_text' => '3',
    'sm_field_ages' => '1',
    'tm_X3b_und_convention_type' => '3',
    'tm_X3b_und_establishment_type_names' => '3',
    'tm_X3b_und_field_actives_specifiques_name' => '3',
    'tm_X3b_und_field_custom_label_permanent' => '6',
    'sm_field_custom_label_temporaire_label' => '6',
    'sm_field_custom_label_permanent_label' => '6',
    'tm_X3b_und_field_types_uo_aptitudes_name' => '3',
    'tm_X3b_und_field_complementary_info' => '3',
    'tm_X3b_und_field_custom_etiquette_etab' => '3',
    'tm_X3b_und_field_department' => '1',
    'sm_field_parent_eg_eg_type_eg_tags_name' => '3',
    'tm_X3b_und_field_finess_equipement_name' => '3',
    'tm_X3b_und_field_finess_equipement_tags_name' => '3',
    'tm_X3b_und_field_finess_equipement_translation' => '3',
    'tm_X3b_und_field_finess_establishment_type_description' => '3',
    'tm_X3b_und_field_finess_establishment_type_finess_etiquette_name' => '3',
    'tm_X3b_und_field_finess_establishment_type_finess_traduction' => '3',
    'sm_field_type_etb_r66_etq' => '3',
    'tm_X3b_und_field_type_etb_r66_synonyme' => '3',
    'sm_field_keywords_name' => '1',
    'sm_field_langues_parlees_name' => '1',
    'tm_X3b_und_field_nom' => '1',
    'tm_X3b_und_field_prenom' => '1',
    'tm_X3b_und_field_precision_type_eg' => '3',
    'tm_X3b_und_field_presentation' => '3',
    'tm_X3b_und_field_presentation_depistage' => '3',
    'tm_X3b_und_field_presentation_vaccination' => '3',
    'tm_X3b_und_field_profession_name' => '6',
    'tm_X3b_und_field_region' => '1',
    'tm_X3b_und_field_service_type_r213_etq' => '3',
    'tm_X3b_und_field_tags_descendants' => '3',
    'tm_X3b_und_field_tags_field_pos_health_center_descendants' => '3',
    'tm_X3b_und_field_tags_pos_health_center_name' => '3',
    'tm_X3b_und_field_tags_name' => '3',
    'tm_X3b_und_field_uo_type_r211_etq' => '3',
    'tm_X3b_und_field_categorie_organisation_r244_etq' => '3',
    'tm_X3b_und_field_categorie_organisation_label_text' => '3',
    'tm_X3b_und_field_temporalite_accueil_label_text' => '3',
    'tm_X3b_und_field_public_pris_en_charge_label_text' => '3',
    'tm_X3b_und_field_types_patients_text' => '3',
    'tm_X3b_und_field_tags_eg_name' => '3',
    'tm_X3b_und_field_phone_number' => '3',
    'tm_X3b_und_uo_type_support_type_ref_name' => '6',
    'tm_X3b_und_field_uo_type_name' => '6',
    'tm_X3b_und_uo_type_support_type_label' => '6',
    'tm_X3b_und_field_ville' => '1',
    'tm_X3b_und_etb_title' => '1',
    'tm_X3b_und_field_information_type_names' => '3',
    'tm_X3b_und_field_specialite_name' => '3',
    'tm_prof_specialities$referring_pivot_term_names' => '3',
    'tm_X3b_und_spec_tags' => '3',
    'tm_X3b_und_tags_ao_mpec' => '3',
    'tm_X3b_und_title' => '15',
    'ss_field_identifiant_rpps' => '3',
    'tm_X3b_und_field_phone_number' => '1',
  ];

  const PRACTITIONER_FIELD_BOOST = [
    'tm_X3b_und_title' => '5.0',
    'ss_field_identifiant_rpps' => '5.0',
  ];

  const PRACTITIONER_PHRASE_FIELD_BOOST = [
    'tm_X3b_und_title' => '5',
    'ss_field_identifiant_rpps' => '5',
  ];

  const PRACTITIONER_NO_RESULT_ERROR = [
    'error_code_sas' => 'sas_pf_001',
    'error_message_sas' => 'Preferred doctor cannot be found.',
  ];

  const PRACTITIONER_NO_PREF_DOC_ERROR = [
    'error_code_sas' => 'sas_pf_002',
    'error_message_sas' => 'missing pref_doctor parameter',
  ];

}
