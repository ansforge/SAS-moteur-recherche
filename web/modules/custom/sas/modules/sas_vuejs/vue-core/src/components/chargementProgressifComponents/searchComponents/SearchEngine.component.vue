<template>
  <div class="hero-banner-search">
    <div
      :class="[
        { inputs: source === 'homepage' },
        { 'd-flex': source !== 'homepage' },
      ]"
    >
      <SearchComboboxSpeciality
        ref="comboboxJob"
        :source="source"
        @confirmed-input="search"
      />

      <SearchComboboxLocation
        ref="comboboxLocation"
        :source="source"
        @confirmed-input="search"
      />
    </div>

    <div class="btn-wrapper">
      <button
        :class="[
          { 'btn-highlight': source === 'homepage' },
          { 'btn-search': source !== 'homepage' },
        ]"
        :disabled="searchIsDisabled"
        type="button"
        @click="search"
      >
        <i v-if="source !== 'homepage'" class="icon-search" aria-hidden="true" />
        <span :class="{ 'sr-only': source !== 'homepage' }">Rechercher</span>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

import { UserService, SettingService } from '@/services';
import { LocalStoragePlugin } from '@/plugins';
import { useUserData, useLrmData } from '@/stores';

import SearchComboboxSpeciality from '@/components/chargementProgressifComponents/searchComponents/SearchComboboxSpeciality.component.vue';
import SearchComboboxLocation from '@/components/chargementProgressifComponents/searchComponents/SearchComboboxLocation.component.vue';

export default {
  name: 'SearchEngine',
  props: {
    source: {
      type: String,
      default: 'homepage',
    },
  },
  components: {
    SearchComboboxSpeciality,
    SearchComboboxLocation,
  },
  setup(props) {
    const comboboxJob = ref(null);
    const comboboxLocation = ref(null);

    const userDataStore = useUserData();
    onMounted(async () => {
      const res = await UserService.getCurrentUser();
      userDataStore.setCurrentUser(res);

      if (props.source === 'header') {
        setLrmSpeciality();
      }
    });

    const lrmDataStore = useLrmData();
    async function setLrmSpeciality() {
      await lrmDataStore.setSpeciality();
    }

    const searchIsDisabled = computed(() => (
      comboboxJob?.value?.input?.length < 3
      || comboboxLocation?.value?.input?.length === 0
    ));

    function search() {
      if (searchIsDisabled.value) return;

      const localStorageSearchText = LocalStoragePlugin.get('search_text');

      if (!localStorageSearchText) {
        LocalStoragePlugin.set('search_text', comboboxJob.value.input);
      } else {
        const localStorageSearchTextTokens = localStorageSearchText.split('|');

        const textFound = localStorageSearchTextTokens.find((x) => x === comboboxJob.value.input);

        if (!textFound) {
          if (localStorageSearchTextTokens.length > 3) {
            localStorageSearchTextTokens.pop();
          }

          localStorageSearchTextTokens.unshift(comboboxJob.value.input);
          LocalStoragePlugin.set('search_text', localStorageSearchTextTokens.join('|'));
        }
      }

      let regulatorTerritories = [];
      if (userDataStore.currentUser.isRegulateurOSNP) {
        userDataStore.currentUser.territory.forEach((element) => {
          regulatorTerritories = [...regulatorTerritories, ...Object.values(element)];
        });
      }

      const locationQuery = `/sas/recherche?text=${encodeURIComponent(comboboxJob.value.input)}&loc=${encodeURIComponent(comboboxLocation.value.input)}`;

      const searchType = props.source === 'header' ? 'header-search-button' : 'homepage-search-button';
      SettingService
      .postSearchLog(searchType, regulatorTerritories)
      .finally(() => {
        window.location.href = locationQuery;
      });
    }

    return {
      comboboxJob,
      comboboxLocation,
      searchIsDisabled,
      search,
    };
  },
};
</script>
