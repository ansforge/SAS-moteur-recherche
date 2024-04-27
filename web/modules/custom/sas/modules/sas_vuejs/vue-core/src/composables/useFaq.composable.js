import ROLES from '@/const/roles.const';
import { FaqService, SettingService } from '@/services';
import { ThemesModel } from '@/models';

export default () => {
  /**
   * @description récupérer le theme FAQ à partir du role de l'utilisateur
   * @param roles
   * @return theme
   */
  async function getThemes(roles) {
    let themesModel = {};
    const highestRole = getHighestRole(roles);
    const faqData = await FaqService.getFaq();
    let themes = faqData.find((obj) => obj.role === highestRole);
    if (!themes) themes = faqData.find((obj) => obj.role === 'anonymous');
    if (themes) themesModel = new ThemesModel({ role: themes.role, themes: themes.themes });
    return themesModel;
  }

  function getHighestRole(currentUserRoles, currentPage = 'faq') {
    // if the user roles are not defined in the array of roles
    let currentRole = 'anonymous';

    // If currentUserRoles is not defined, return default current role
    if (!currentUserRoles) return currentRole;

    // Set the roles from the higher to the lower (hierarchie)
    const roles = getAllRoles();
    // Find the first index of the higher user role
    const higherRoleIndex = roles.findIndex((role) => currentUserRoles.find((userRole) => userRole === role.value));
    if (higherRoleIndex > -1) {
      // Faq page need .value to fetch themes & reorientation need label to get default role value in form
      currentRole = currentPage === 'faq' ? roles[higherRoleIndex].value : roles[higherRoleIndex].label;
    } else {
      currentRole = 'anonymous';
    }

    return currentRole;
  }

  function getAllRoles() {
    return Object.values(ROLES);
  }

  async function uploadFormContact(formData) {
    return FaqService.uploadFormData(formData);
  }

  async function getTerritories() {
    // eslint-disable-next-line no-return-await
    return SettingService.getTerritories();
  }

  return {
    getThemes,
    getAllRoles,
    getHighestRole,
    uploadFormContact,
    getTerritories,
  };
};
