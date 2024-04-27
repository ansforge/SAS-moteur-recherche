import {
    SAS_API_DRUPAL,
    SAS_AGGREGATOR_EDITOR_LIST,
    SAS_AGGREGATOR_USER_EDITOR_LIST,
    SAS_AGGREGATOR_EDITOR_EFFECTOR,
} from '@/const';
import SettingService from '@/services/setting.service';
import { cookie } from '@/helpers';
import { ApiPlugin } from '@/plugins';

let controller;
export default class DashboardClass {
    static async getDashboardUserSettings(rppsAdeli) {
        let res = null;
        try {
            res = await ApiPlugin.get(`${SAS_API_DRUPAL}/drupal/user/${rppsAdeli}/settings`);
        } catch (err) {
            console.warn(`Error fetching getDashboardUserSettings : ${err}`);
        }
        return res?.data || {};
    }

    static async updateDashboardUserSettings(payload, rppsAdeli, isFirstSave) {
        let res = null;
        const reqMethod = isFirstSave ? 'post' : 'patch';
        const reqUri = isFirstSave ? `${SAS_API_DRUPAL}/drupal/user/settings` : `${SAS_API_DRUPAL}/drupal/user/${rppsAdeli}/settings`;
        const token = await this.getDrupalToken();
        try {
            res = await ApiPlugin[reqMethod](
                reqUri,
                payload,
                {
                    headers: {
                      common: {
                        'X-CSRF-TOKEN': token,
                      },
                    },
                },
            );
        } catch (err) {
            console.error('Error during the dashboard settings update registration : ', err);
        }
        return res?.data || {};
    }

    static async getDrupalToken() {
        let res = null;
        try {
            res = await ApiPlugin.get('/session/token');
        } catch (err) {
            console.error('Error during fetch drupal token');
        }
        return res?.data || null;
    }

    static async getEditorList() {
        let res = null;
        try {
            res = await ApiPlugin.get(
                `${SAS_AGGREGATOR_EDITOR_LIST}`,
                {
                    headers: {
                      Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
                    },
                },
            );
        } catch (err) {
            console.warn(`Error fetching getEditorList : ${err}`);
        }
        return res?.data || {};
    }

    static async fetchUserEditorList(rppsAdeli) {
        let res = null;
        try {
            res = await ApiPlugin.get(
                `${SAS_AGGREGATOR_USER_EDITOR_LIST}/${rppsAdeli}`,
                {
                    headers: {
                      Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
                    },
                },
            );
        } catch (err) {
            console.warn(`Error fetching fetchUserEditorList : ${err}`);
        }
        return res?.data || {};
    }

    static async putUserEditorList(payload, rppsAdeli) {
        let res = null;
        try {
            if (!cookie.getCookie('sas_aggregator_token')) {
                await SettingService.getAggregatorToken();
              }
            res = await ApiPlugin.put(
                `${SAS_AGGREGATOR_EDITOR_EFFECTOR}/${rppsAdeli}/bulk`,
                payload,
                {
                    headers: {
                      Authorization: `bearer ${ cookie.getCookie('sas_aggregator_token')}`,
                    },
                },
            );
        } catch (err) {
            console.warn(`Error fetching fetchUserEditorList : ${err}`);
        }
        return res?.status || null;
    }

    static async getAutocompleteList(req, type) {
        let res = null;
        try {
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();
            res = await ApiPlugin.get(`${SAS_API_DRUPAL}/drupal/structure/list/${type}?search=${req}`, { signal: controller.signal });
        } catch (err) {
            if (err.message !== 'canceled') {
                console.warn(`Error fetching getAutocompleteList : ${err}`);
            }
        }
        return res?.data || {};
    }

    static async fetchAutocompleteLabel(idType, structureId) {
        let res = null;
        try {
            res = await ApiPlugin.get(`${SAS_API_DRUPAL}/drupal/structure/${idType}/${structureId}/info`);
        } catch (err) {
            console.warn(`Error fetching fetchAutocompleteLabel : ${err}`);
        }
        return res?.data || null;
    }

    static async getEffectorAddressList(rppsAdeli) {
        let res = null;
        try {
            res = await ApiPlugin.get(`${SAS_API_DRUPAL}/drupal/user/${rppsAdeli}/addresses`);
        } catch (err) {
            console.warn(`Error fetching getDashboardUserSettings : ${err}`);
        }
        return res?.data || {};
    }
}
