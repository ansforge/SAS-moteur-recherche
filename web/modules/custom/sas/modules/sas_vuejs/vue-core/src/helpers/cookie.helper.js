import { useCookies } from 'vue3-cookies';

const { cookies } = useCookies();
export default {
  getCookie(name) {
    return cookies.get(name);
  },

  setCookie(name = '', value = '', exp = 60 * 60) {
    cookies.set(name, value, exp);
  },

  removeCookie(name) {
    cookies.remove(name);
  },
};
