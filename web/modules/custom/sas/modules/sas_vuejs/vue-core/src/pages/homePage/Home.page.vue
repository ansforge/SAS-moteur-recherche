<template>
  <div v-if="!isLoading">
    <component :is="currentComponentName" v-bind="currentComponentProps" />
  </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue';
import HomeDisconnected from '@/components/home/HomeDisconnected.component.vue';
import HomeConnected from '@/components/home/HomeConnected.component.vue';
import { useHome } from '@/composables';

export default {
  components: { HomeDisconnected, HomeConnected },
  setup() {
    const {
      getContentHome,
      isLoading,
    } = useHome();
    const contentHome = ref(null);

    onMounted(async () => {
      contentHome.value = await getContentHome();
    });

    const currentComponentName = computed(() => (contentHome.value?.getIsConnected() ? HomeConnected.name : HomeDisconnected.name));
    const currentComponentProps = computed(() => (contentHome.value?.getIsConnected() ? contentHome.value?.getConnectedData() : contentHome.value?.getDisconnectedData()));

    return {
      currentComponentName,
      currentComponentProps,
      isLoading,
    };
  },
};
</script>
