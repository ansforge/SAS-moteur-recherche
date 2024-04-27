import { cookie } from '@/helpers';
import ROLES from '@/const/roles.const';

export default class UserClass {
  constructor(userData = {}) {
    this.firstname = userData.firstname || '';
    this.lastname = userData.lastname || '';
    this.email = userData.email || '';
    this.county = userData.county || '';
    this.countyNumber = userData.county_number || '';
    this.territoryApiId = userData.territory_api_id || [];
    this.territory = userData.territory || [];
    this.current_user_timezone = userData.current_user_timezone || 'Europe/Paris';
    this.roles = userData.roles || [];
    this.isPscUser = !!cookie.getCookie('sas_authenticated_ps');
    this.rpps_adeli = userData.rpps_adeli || '';
  }

  get isRegulateurIOA() {
    return this.roles.some((r) => r === ROLES.IOA.value);
  }

  get isRegulateurOSNP() {
    return this.roles.some((r) => r === ROLES.REGULATEUR_OSNP.value);
  }

  get isRegulateurOSNPorIOA() {
    return this.isRegulateurOSNP || this.isRegulateurIOA;
  }

  getRoles() {
    return this.roles;
  }
}
