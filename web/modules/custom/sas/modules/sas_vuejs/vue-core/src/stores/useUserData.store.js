import { defineStore } from 'pinia';
import { ref } from 'vue';
import UserModel from '@/models/user/User.model';

/* eslint-disable import/prefer-default-export */
export const useUserData = defineStore('userData', () => {
  const currentUser = ref({});
  function setCurrentUser(userInfo) {
    currentUser.value = new UserModel(userInfo);
  }

  return {
    currentUser,
    setCurrentUser,
  };
});
