import { Slot } from "@/types/Slot";

/**
 * The interface common to all types of cards used in the SAS.
 * Properties starting with `tm_x3b_und_` are send by SolR.
 */
export interface ICard {
    type: 'default' | 'cpts' | 'sos';

    /** Id constructed by solr and drupal */
    id: string;

    /** Solr collection identity */
    index_id: string;

    /**
     * Unique id from Drupal or constructed by us (aggregator for example)
     */
    its_nid: number;

    /** Of the form: `lat,long`. Example: `48.831600,2.280600` */
    locs_field_geolocalisation_latlon: string;

    /** To tell if the card comes from aggregator or sas api */
    origin?: string;

    score: number;
    ss_field_address: string;
    ss_field_codepostal: string;
    ss_field_custom_group: string;
    ss_field_department_code: string;
    ss_field_identif_siret?: string;
    ss_field_node_path_alias: string;
    ss_field_region_code: string;
    ss_field_street: string;
    ss_type: string

    ss_sas_additional_info?: string;

    tm_X3b_und_establishment_type_names: string[];
    tm_X3b_und_field_department: string[];
    tm_X3b_und_field_phone_number: string[];
    tm_X3b_und_field_region: string[];
    tm_X3b_und_field_ville: string[];
    tm_X3b_und_title: string[];
}

/**
 * Every information available for an health offer card
 */
export interface Card extends ICard {
    /** Settable in the effector dashboard */
    bs_sas_editor_disabled: boolean;

    bs_sas_forfait_reo: boolean;

    /** True if the health offer participate to the SAS */
    bs_sas_participation: boolean;
    bs_sas_overbooking: boolean;

    /** Of the form: `YYYY-MM-DDTHH:mm:ssZ` */
    ds_changed: string;

    finalAddress: string;

    /** Of the form: XX XX XX XX XX */
    final_phone_number: string;

    isSasApi: boolean;
    isSOSMedecin: boolean;

    itm_convention_type_number: number[];
    itm_establishment_types: number[];

    its_field_profession: number;

    /**
     * 1: *individual*  
     * 2: CPTS  
     * 3: MSP  
     * 4: SOS  
     */
    its_sas_participation_via: number;


    sasParticipationLabel: string;
    sasForfaitReuLabel: string;

    scheduleData: Schedule;

    score: number;

    slotList: SlotList;
    slotTable: SlotTable;

    /** Of the form: `n|HHmm|HHmm||7` */
    sm_field_horaires: string[];

    sm_sas_territory_labels: string[];
    sm_sas_territory_ids: string[];

    ss_field_identifiant: string;
    ss_field_identifiant_active_rpps: string;
    ss_field_identifiant_rpps: string;
    ss_field_site_internet: string;
    ss_field_site_internet_title: string;

    ss_sas_cpts_finess?: string;
    ss_sas_cpts_label?: string;
    ss_sas_cpts_phone?: string[];
    ss_sas_timezone: string;

    tm_X3b_und_convention_type?: string[];
    tm_X3b_und_field_nom: string[];
    tm_X3b_und_field_precision_type_eg: string[];
    tm_X3b_und_field_prenom: string[];
    tm_X3b_und_field_profession_name: string[];
    tm_X3b_und_field_specialite_name: string[];
}

/**
 * Every information available for a cpts card
 */
export interface CptsCard extends ICard {
    bs_field_urgences: boolean;
    sm_sas_cpts_care_deal_phones: string[];
    sm_sas_intervention_zone_insee: string[];

    ss_etb_address: string;
    ss_etb_item_id: string;
    ss_etb_path_alias: string;

    ss_field_ident_service_sante_ror: string;
    ss_field_identifiant_finess: string;

    tm_X3b_und_etb_telephones: string[];
    tm_X3b_und_etb_title: string[];
}


export interface Schedule {
    horaireTraite: Array;
    nextTime?: any;
    nextTimeMsg: string;
    open: boolean;
}

export interface SlotList {
    afterTomorrow: Slot[];
    today: Slot[];
    tomorrow: Slot[];
}

export interface SlotTable {
    next12to24Hours: Slot[];
    next24to48Hours: Slot[];
    next48to72Hours: Slot[];
    next4Hours: Slot[];
    next8to12Hours: Slot[];
}
