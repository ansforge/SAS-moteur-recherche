import { ref } from 'vue';
import { HomeService } from '@/services';
import { HomeContentModel } from '@/models';

export default () => {
  const isLoading = ref(true);

  /**
   * @description get and map the content home page
   * @return {HomeContentModel}
   */
  const getContentHome = async () => {
    const content = await HomeService.getContent();
    const isLoggedIn = content.user_is_logged_in;

    isLoading.value = false;

    return new HomeContentModel({
      isConnected: isLoggedIn,
      description: isLoggedIn ? content.hp_logged_user_text : content.hp_unlogged_user_text,
      subDescription: isLoggedIn ? content.hp_logged_user_text_1 : content.hp_logged_user_text_1,
      objectives: isLoggedIn ? null : content.hp_unlogged_user_objectives,
      bgImage: isLoggedIn ? content.hp_homepage_image : content.hp_homepage_image_unlogged,
      bgImageMobile: isLoggedIn ? content.hp_bg_image_mobile : null,
    });
  };

  return {
    getContentHome,
    isLoading,
  };
};
