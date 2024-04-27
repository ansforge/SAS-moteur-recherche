<template>
  <div class="tabs-container">
    <h3 v-if="tabListTitle && !store.showCurrentUserLoader" id="tablist-title">{{ tabListTitle }}</h3>
    <h3 v-else id="tablist-title">Chargement en cours</h3>

    <div role="tablist" aria-labelledby="tablist-title" class="tablist-wrapper">
      <button
        v-for="tabItem of tabList"
        :id="tabItem.id"
        type="button"
        role="tab"
        class="tab-item"
        :class="{ selected: tabItem.id === currentTab.id }"
        :aria-selected="tabItem.id === currentTab.id"
        :aria-controls="`tabpanel-${tabItem.id}`"
        :key="tabItem.id"
        @click="handleClick(tabItem)"
      >
        <span class="tab-item-label">{{ tabItem.label }}</span>
      </button>
    </div>
  </div>
</template>

<script>
import { useUserDashboard } from '@/stores';

export default {
  props: {
    tabList: {
      type: Array,
      default: () => ([]),
    },
    currentTab: {
      type: Object,
      default: () => ({}),
    },
    tabListTitle: {
      type: String,
      default: '',
    },
  },
  emits: [
    'changeTab',
    'update-schedule',
  ],
  setup(props, { emit }) {
    const store = useUserDashboard();

    function handleClick(tabItem) {
      emit('changeTab', tabItem);
      if (tabItem.id === 'adresses') {
        emit('update-schedule');
      }
    }

    return {
      store,
      handleClick,
    };
  },
};
</script>
