
export interface User {
    email: string;
    firstname: string;
    lastname: string;
    rpps_adeli: string;
    city: string;
    county: string;
    county_number: string;
    territory: Map<string, string>;
    territory_tid: string[];
    territory_api_id: string[];
    region: string;
    is_sas: boolean;
    current_user_timezone: string;
    roles: string[];
    user_is_logged_in: boolean;
}


export interface FilterDictionnary {
    itm_convention_type_number: string[];

    itm_establishment_types: Map<string, string>;

    itm_field_custom_label_permanent_tid: Map<string, string>;

    itm_field_tc_custom_label_permanent_tid: Map<string, string>;

    im_field_types_horaires: Map<string, string>;

    itm_field_maternite_level_tid: Map<string, string>;

    itm_field_categorie_organisation: Map<string, string>;
}

export interface Location {
    city: string;
    countyCode: string;
    countyName: string;
    defaultRadius: number;
    enlargementValue: number;
    fullAddress: string;
    houseNumber: string;
    inseeCode: string;
    latitude: number;
    longitude: number;
    postCode: string;
    score: number;
    source: string;
    street: string;
    type: string;
}