<template>
  <div class="job-search">
    <input
      ref="comboboxNode"
      v-model="input"
      class="input-what"
      placeholder="Renseigner une spécialité, une structure ou un nom"

      type="text"
      role="combobox"
      aria-label="Renseigner une spécialité, une structure ou un nom (dernières recherches et recherches fréquentes en ce moment disponibles ci-après)"
      aria-autocomplete="list"
      aria-controls="history-searches-listbox popular-searches-listbox"
      :aria-expanded="listIsVisible"

      @input="handleInput"
      @keyup.enter="confirmInput"
      @focus="displayList"
      @focusout="hideList"
    />
    <button
      v-if="input && input.length"
      type="button"
      class="clear-search clear-main-search"
      @click="clearInput"
    >
      <span class="sr-only">Vider le champ "Renseigner une spécialité, une structure ou un nom"</span>
      <i class="icon icon-close-circle-solid" />
    </button>
  </div>

  <div
    v-if="listIsVisible"
    ref="listboxWrapperNode"
    class="short-search-wrapper historical-searches"
    role="listbox"
    aria-expanded="true"
  >
    <Listbox
      v-if="showTextHistory && textHistoryList.length"
      key="history-searches"
      class="history-searches"
      listId="history-searches"
      :items="textHistoryList"
      header="Dernières recherches"
      iconClass="icon-history"
      @clicked-on-list-item="selectListItem"
    />

    <Listbox
      v-if="result?.length"
      key="popular-searches"
      class="recent-searches popular-searches"
      listId="popular-searches"
      :items="result"
      header="Recherches suggérées"
      @clicked-on-list-item="selectListItem"
    />
  </div>
</template>

<script>
import {
 ref,
 onMounted,
 watch,
 nextTick,
} from 'vue';

import { routeHelper } from '@/helpers';
import { SearchEngine } from '@/services';
import { LocalStoragePlugin } from '@/plugins';
import { useLrmData } from '@/stores';

import Listbox from '@/components/chargementProgressifComponents/Listbox.component.vue';
import { useCombobox } from '@/composables';

export default {
  name: 'SearchComboboxSpeciality',
  props: {
    source: {
      type: String,
      default: 'homepage',
    },
  },
  components: {
    Listbox,
  },
  emits: ['confirmed-input'],
  setup(props, { emit }) {
    const combobox = useCombobox({
      emit, inputHandler, beforeGetResultList, displayResultList,
    });
    const searchDefaultSuggestions = ref([]);

    const showTextHistory = ref(false);
    const textHistoryList = ref([]);

    const lrmDataStore = useLrmData();

    onMounted(() => {
      if (LocalStoragePlugin.get('search_text')) {
        textHistoryList.value = LocalStoragePlugin.get('search_text').split('|');
      }

      if (props.source === 'header') {
        combobox.input.value = routeHelper.getUrlParam('text');
      }

      getDefaultTextList();
    });

    // wait for lrm speciality
    watch(() => lrmDataStore.speciality, (newLrmSpeciality) => {
      if (props.source === 'header') {
        combobox.input.value = newLrmSpeciality || routeHelper.getUrlParam('text');
      }
    });

    async function getDefaultTextList() {
      searchDefaultSuggestions.value = await SearchEngine.getSearchSuggestions();
    }

    /**
     * @param {string} searchText
     */
    async function inputHandler(searchText, abortSignal) {
      const finalSearchText = searchText.trim();
      if (finalSearchText === '') {
        return [];
      }

      return SearchEngine.getSearchSuggestionsByText(finalSearchText, abortSignal);
    }

    function beforeGetResultList() {
      if (!this.input.value || this.input.value.trim().length < 3) {
        this.displayList();
        return true;
      }
      return false;
    }

    function displayResultList() {
      this.listIsVisible.value = true;
      showTextHistory.value = true;

      if (!this.input.value) {
        this.result.value = [...searchDefaultSuggestions.value];
        return;
      }

      showTextHistory.value = false;
      nextTick(() => {
        this.forceReload();
      });

      if (this.input.value.length >= 3 && this.hasNoResult.value) {
        this.hideList();
      }
    }

    function handleInput(e) {
      if (e.target.value === '') {
        combobox.getResultListDebounce(0);
      } else {
        combobox.getResultListDebounce(300);
      }
    }

    return {
      ...combobox,
      handleInput,
      showTextHistory,
      textHistoryList,
    };
  },
};
</script>
