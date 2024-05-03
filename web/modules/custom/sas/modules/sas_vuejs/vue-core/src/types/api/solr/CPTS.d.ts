import { ICard } from "@/types/Card";


export interface CPTSCard extends ICard {
    bs_field_urgences: boolean;
    ds_changed: string;

    sm_sas_intervention_zone_insee: string[];

    ss_etb_address: string;
    ss_etb_item_id: string;
    ss_etb_path_alias: string;

    ss_field_ident_service_sante_ror: string;
    ss_field_identif_siret: string;
    ss_field_identifiant_finess: string;

    // Logically, it must contains this string: 'Communauté Professionnelle Territoriale de Santé (CPTS)'
    tm_X3b_und_etb_telephones: string[];
    tm_X3b_und_etb_title: string[];
}
