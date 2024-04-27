<template>
  <div class="formation-article">
    <div class="formation-header">
      <div class="container-full">
        <p class="formation-header-label">Espace de formation</p>

        <a href="/formation" class="formation-header-back-home">
          <i class="sas-icon sas-icon-back" aria-hidden="true" />
          Retour à l'accueil
        </a>

        <p v-if="currentRole.roleData.title" class="formation-header-category">
          Vous êtes : {{ currentRole.roleData.title }}
        </p>
      </div>
    </div>

    <div class="container-full">
      <div class="formation-article-wrapper">
        <div class="formation-article-cols d-flex">
          <MenuBloc
            :menuItems="formationMenuLinks"
            :currentSelected="currentMenuData"
          />

          <FormationPageContent
            :currentPageData="currentPageData"
            :themeTitle="currentTheme.themeData.title"
            :currentAvailablePages="currentAvailablePages"
            :currentMenuData="currentMenuData"
          />

          <button
            id="sas-go-to-top-page"
            class="sas-go-to-top-page"
            type="button"
            @click="scrollToTop()"
          >
            <i class="icon-arrow" />
            HAUT DE PAGE
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {
 ref, computed, onMounted,
} from 'vue';
import { FormationService } from '@/services';

import MenuBloc from '@/components/formationComponents/FormationMenu.component.vue';
import FormationPageContent from '@/components/formationComponents/FormationPageContent.component.vue';

export default {
  name: 'Formation',
  components: {
    MenuBloc,
    FormationPageContent,
  },
  setup() {
    const currentAvailablePages = ref([]);
    const formationMenuLinks = ref([]);
    const currentPageData = ref({});
    const currentMenuData = computed(() => {
      const menuData = currentPageData.value.menu || [];

      return {
        currentRole: menuData.length > 2 ? menuData[2] : null,
        currentTheme: menuData.length > 1 ? menuData[1] : null,
        currentPage: menuData.length > 0 ? menuData[0] : null,
      };
    });

    const currentRole = computed(() => {
      const idRole = currentMenuData.value.currentRole;
      const roleData = formationMenuLinks.value.filter((menuItems) => menuItems.key === idRole);

      return {
        idRole,
        roleData: roleData.length > 0 ? roleData[0] : {},
      };
    });

    const currentTheme = computed(() => {
      const idTheme = currentMenuData.value.currentTheme;
      const themeData = currentRole.value?.roleData?.below
        ? currentRole.value.roleData.below.filter((themeItems) => themeItems.key === idTheme) : [];

      return {
        idTheme,
        themeData: themeData.length > 0 ? themeData[0] : {},
      };
    });

    // Get all available pages in a simple array for the pagination
    function getAvailablePages(pagesTree) {
      pagesTree.forEach((currentLvl) => {
        if (currentLvl.below?.length > 0) {
          getAvailablePages(currentLvl.below);
        } else {
          currentAvailablePages.value.push(currentLvl);
        }
      });
    }

    /**
     * Scroll to top
     */
     function scrollToTop() {
      window.scrollTo(0, 0);
    }

    onMounted(async () => {
      const currentNodeId = window.API?.node_uuid || null;

      try {
        formationMenuLinks.value = await FormationService.getMenuFormationData();
        currentPageData.value = await FormationService.getPageContent(currentNodeId);
        getAvailablePages(currentRole.value.roleData.below || []);
      } catch (e) {
        console.error(e);
      }
    });

    return {
      formationMenuLinks,
      currentPageData,
      currentAvailablePages,
      currentMenuData,
      currentRole,
      currentTheme,
      scrollToTop,
    };
  },
};
</script>
