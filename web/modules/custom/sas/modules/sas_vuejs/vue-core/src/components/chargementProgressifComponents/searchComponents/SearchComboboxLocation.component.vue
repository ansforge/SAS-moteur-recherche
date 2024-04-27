<template>
  <div class="locality-search">
    <input
      ref="comboboxNode"
      v-model="input"
      class="input-where"
      placeholder="Renseigner une adresse"

      type="text"
      role="combobox"
      aria-haspopup="listbox"
      aria-label="Renseigner une adresse"
      aria-autocomplete="list"
      aria-controls="location-counties-searches-listbox location-citites-searches-listbox location-addresses-searches-listbox"
      :aria-expanded="listIsVisible"

      @input="getResultListDebounce(300)"
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
      <span class="sr-only">Vider le champ "Renseigner une adresse"</span>
      <i class="icon icon-close-circle-solid" />
    </button>
  </div>

  <div
    v-if="listIsVisible"
    ref="listboxWrapperNode"
    class="short-search-wrapper place-searches"
    role="listbox"
    aria-expanded="true"
  >
    <Listbox
      v-if="result?.counties?.length"
      header="Départements"
      listId="location-counties-searches"
      :items="result.counties"
      @clicked-on-list-item="selectListItem"
    />
    <Listbox
      v-if="result?.cities?.length"
      header="Villes"
      listId="location-citites-searches"
      :items="result.cities"
      @clicked-on-list-item="selectListItem"
    />
    <Listbox
      v-if="result?.addresses?.length"
      header="Adresses"
      listId="location-addresses-searches"
      :items="result.addresses"
      @clicked-on-list-item="selectListItem"
    />
  </div>
</template>

<script>
import { onMounted } from 'vue';
import { routeHelper } from '@/helpers';
import { useLrmData } from '@/stores';
import Listbox from '@/components/chargementProgressifComponents/Listbox.component.vue';
import { useCombobox } from '@/composables';
import { GeolocationService } from '@/services';

export default {
  name: 'SearchComboboxLocation',
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
    const combobox = useCombobox({ emit, inputHandler });
    const lrmDataStore = useLrmData();

    onMounted(() => {
      if (props.source === 'header') {
        combobox.input.value = lrmDataStore.address || routeHelper.getUrlParam('loc');
      }
    });

    function formatResult(result) {
      const { county, city, address } = result.data || {};

      return {
        counties: county?.map((co) => (co.fullAddress)),
        cities: city?.map((ci) => (ci.fullAddress)),
        addresses: address?.map((a) => (a.fullAddress)),
      };
    }

    /**
     * @param {string} searchText
     */
    async function inputHandler(searchText, abortSignal) {
      const finalSearchText = searchText.trim();
      if (finalSearchText.length < 3) {
        return {};
      }
      const result = await GeolocationService.getLocationByName(finalSearchText, abortSignal);
      return formatResult(result);
    }

    return combobox;
  },
};
</script>
