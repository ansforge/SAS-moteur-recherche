export interface User {
    firstname: string;
    lastname: string;
    email: string;

    county: string;
    countyNumber: string;

    territoryApiId: string[];
    territory: Array<{ [key: string]: string }>;

    current_user_timezone: string;

    roles: string[];

    isPscUser: boolean;

    rpps_adeli: string;
}
