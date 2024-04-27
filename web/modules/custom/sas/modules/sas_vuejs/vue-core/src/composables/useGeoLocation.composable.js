import { cookie } from '@/helpers';

export default () => {
  function getRegionTid() {
    const str = cookie.getCookie('region_tid');
    if (!str) return {};
    try {
      const regionCookie = JSON.parse(decodeURIComponent(str));
      return { region_id: (regionCookie.user_selected !== null) ? regionCookie.user_selected : regionCookie.detected };
    } catch (e) {
      console.log(e);
      return {};
    }
  }
  return {
    getRegionTid,
  };
};
