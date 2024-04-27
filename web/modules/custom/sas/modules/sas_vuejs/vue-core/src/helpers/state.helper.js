// helper.js
import { computed } from 'vue';

const withState = (target, state) => {
  Object.keys(state).forEach((prop) => {
    // eslint-disable-next-line no-param-reassign
    target[prop] = computed(() => state[prop]);
  });
  return target;
};
export default withState;
