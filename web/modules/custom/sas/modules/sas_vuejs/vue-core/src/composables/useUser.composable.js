import { ref } from 'vue';
import { UserService } from '@/services';
import { UserModel } from '@/models';
import { cookie } from '@/helpers';

export default () => {
  const currentUser = ref({});

  /**
   * @description map the current user to the model user
   */
  const mapUserToUserModel = (user) => new UserModel({
    lastname: user.lastname,
    firstname: user.firstname,
    roles: user.roles,
    email: user.email,
    county: user.county,
    county_number: user.county_number,
    territory: user.territory,
    territory_api_id: user.territory_api_id,
    current_user_timezone: user.current_user_timezone,
    rpps_adeli: user.rpps_adeli,
  });

  /**
   * @description Get if the user is connected as a pro sante connect user
   * @returns {boolean}
   */
  const isPscUser = () => {
    const isPscUserCookie = cookie.getCookie('sas_authenticated_ps');
    return !!isPscUserCookie;
  };

  /**
   * @description get the current user and map it
   */
  function getCurrentUser() {
    return UserService.getCurrentUser()
      .then((response) => {
        currentUser.value = mapUserToUserModel(response);
      });
  }

  return {
    currentUser,
    getCurrentUser,
    isPscUser,
  };
};
