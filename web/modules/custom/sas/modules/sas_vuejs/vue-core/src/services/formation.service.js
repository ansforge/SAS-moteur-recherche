import { ApiPlugin } from '@/plugins';

export default class FormationClass {
  static async getMenuFormationData() {
    let res = null;

    try {
      res = await ApiPlugin.get('/api/menu_items/menu-sas-formation-menu');
    } catch (e) {
      console.error('Error fetching formation menu data \n', e);
    }

    // TODO: temporary solution to be removed
    const menuLinks = res?.data || [];
    menuLinks.forEach((role, idx) => {
      if (
        role.below?.length
        && role.below[0].below?.length
      ) {
        const firstPage = role.below[0].below[0];
        menuLinks[idx].absolute = firstPage.absolute;
      }
    });

    return menuLinks;
  }

  static async getPageContent(nodeId) {
    let res = null;

    try {
      res = await ApiPlugin.get(`/api/sas/formation/content/${nodeId}`);
    } catch (e) {
      console.error('Error fetching formation page content \n', e);
    }

    return res?.data || {};
  }
}
