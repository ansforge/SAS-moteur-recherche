import ROLES from './roles.const';

export default {
  TAB_LIST: [
    { id: 'adresses', label: 'Vos adresses', roles: [ROLES.EFFECTEUR.value] },
    // { id: 'delegataires', label: 'Vos délégataires', roles: [ROLES.EFFECTEUR.value] },
    // { id: 'dashboard', label: 'Tableau de bord', roles: [ROLES.EFFECTEUR.value] },
    // { id: 'account', label: 'Votre compte', roles: [ROLES.EFFECTEUR.value] },
  ],
};
