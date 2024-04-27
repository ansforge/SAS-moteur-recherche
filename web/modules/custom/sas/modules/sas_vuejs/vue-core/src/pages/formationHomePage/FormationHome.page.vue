<template>
  <div class="formation-home-wrapper">
    <div class="formation-home-content">
      <h1 class="formation-home-main-title" v-html="$sanitize(pageTitle)" />

      <div class="formation-home-nav">
        <p>Vous êtes :</p>

        <ul>
          <li
            v-for="menuItem in formationMenuLinks"
            :key="menuItem.key"
          >
            <a :href="$sanitizeUrl(menuItem.absolute)">{{ menuItem.title }}</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { FormationService } from '@/services';

export default {
  name: 'FormationHome',
  components: {},
  setup() {
    const formationMenuLinks = ref([]);
    const pageData = ref({});
    const pageTitle = computed(() => pageData.value.formation_title || 'Bienvenue sur <br>l\'espace de formation du Service d\'Accès aux Soins');

    /**
     * TODO
     * Third Level return is not working
     * function to be improved
     * @param {*} menuItem
     */
    function getFirstLevelLinks(menuItem = {}) {
      if (menuItem.below?.length) {
        getFirstLevelLinks(menuItem.below[0]);
      }

      return menuItem.absolute;
    }

    onMounted(async () => {
      pageData.value = window.API || {};

      try {
        formationMenuLinks.value = await FormationService.getMenuFormationData();
      } catch (e) {
        console.error(e);
      }
    });

    return {
      formationMenuLinks,
      pageData,
      pageTitle,
      getFirstLevelLinks,
    };
  },
};
</script>
