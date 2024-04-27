<template>
  <div class="formation-article-nav">
    <input type="checkbox" id="nav-toggle-xs" aria-expanded="false" />
    <label for="nav-toggle-xs">Choisissez une thématique <i class="icon-up" aria-hidden="true" /></label>
    <div class="accordion-wrapper">
      <Accordion
        v-for="(item) in currentMenuItems"
        :defaultState="item.key === currentSelected.currentTheme"
        :key="item.key"
      >
        <template #title><span>{{ item.title }}</span></template>
        <template #content>
          <ul v-if="item.below && item.below.length > 0">
            <li
              v-for="(page) in item.below"
              :key="page.key"
            >
              <a
                class="title-child"
                :class="{ selected: page.key === currentSelected.currentPage }"
                :href="$sanitizeUrl(page.relative)"
              >
                <strong v-if="page.key === currentSelected.currentPage">{{ page.title }}</strong>
                <template v-else>{{ page.title }}</template>
              </a>
            </li>
          </ul>
        </template>
      </Accordion>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

import Accordion from '@/components/sharedComponents/Accordion.component.vue';

export default {
  props: {
    menuItems: {
      type: Array,
      default: () => ([]),
    },
    currentSelected: {
      type: Object,
      default: () => ({}),
    },
  },
  components: { Accordion },
  setup(props) {
    const currentMenuItems = computed(() => {
      const roleData = props.menuItems.filter((menuItems) => menuItems.key === props.currentSelected.currentRole);

      return (roleData.length > 0 && roleData[0].below)
        ? roleData[0].below : [];
    });

    return {
      currentMenuItems,
    };
  },
};
</script>
