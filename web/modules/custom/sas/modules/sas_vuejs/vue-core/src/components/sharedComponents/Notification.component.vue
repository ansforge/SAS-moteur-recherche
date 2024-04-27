<template>
  <div class="notice-wrapper" :class="currentView">
    <div
      :class="[
        { 'notice-error': status === 'error' },
        { 'informative-banner': status === 'info' },
        { 'notice-success': status === 'success' },
      ]"
      v-bind="roleNotificationElem"
    >
      <p v-if="message" class="notice-message" v-html="$sanitize(message)" />
      <p v-else class="notice-message">
        <slot />
      </p>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'Notification',
  props: {
    status: { type: String, default: 'info' },
    currentView: { type: String, default: '' },
    message: { type: String, default: '' },
  },
  setup(props) {
    const roleNotificationElem = computed(() => ({ role: props.status === 'error' ? 'alert' : 'status' }));

    return {
      roleNotificationElem,
    };
  },
};
</script>
