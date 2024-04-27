<template>
  <div>App vue</div>
</template>

<script>
import { useMobileDetection } from '@/composables';

export default {
  name: 'App',
  setup() {
    useMobileDetection();

    return {};
  },
};
</script>
