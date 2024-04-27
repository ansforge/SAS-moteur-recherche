<template>
  <RingLoader v-if="showStructureLoader" />
  <div v-else :class="`${autocompleteType.name}-autocomplete`">
    <div class="autocomplete-wrap">
      <label>{{ autocompleteType.label }} <span aria-hidden="true">*</span><span class="sr-only">Champ obligatoire</span></label>
      <div class="dropdown">
        <input
          type="text"
          autocomplete="off"
          :name="autocompleteType.name"
          @keyup="fetchAutocomplete($event, autocompleteType.name)"
          @focus="showAutocompleteList = true"
          @focusout="handleFocusOut"
          v-model="currentAutocompleteValue"
        />

        <ul v-if="showAutocompleteList && autocompleteListArray.length">
          <li v-for="val in autocompleteListArray" :key="`autocomplete-${val.id_structure}`">
            <button type="button" @mousedown="searchText(val)">
              {{ val.title }} ({{ val.id_structure }})
            </button>
          </li>
        </ul>
      </div>

      <p v-if="sasParticipationVia === 4" class="mail-support">En cas de difficulté à trouver votre association SOS Médecins, contacter le support webform-n3.sas@esante.gouv.fr</p>
    </div>

    <p
      v-if="
        (!autocompleteCheck.check && autocompleteCheck.error?.length)
          || !autocompleteValue
          || !currentAutocompleteValue
      "
      class="error-msg"
      v-html="$sanitize(autocompleteCheck.error)"
    />
  </div>
</template>

<script>
import {
 ref, computed, onMounted, watch,
} from 'vue';
import DashboardService from '@/services/dashboard.service';
import { useDashboard } from '@/composables';
import RingLoader from '@/components/sharedComponents/loader/RingLoader.component.vue';

export default {
  components: {
    RingLoader,
  },
  emits: ['get-autocomplete-val'],
  props: {
    sasParticipationVia: {
      type: Number,
      default: null,
    },
    autocompleteValue: {
      type: String,
      default: '',
    },
    label: {
      type: String,
      default: '',
    },
    showStructureLoader: {
      type: Boolean,
      default: false,
    },
  },
  setup(props, { emit }) {
    const showAutocompleteList = ref(false);
    // Autocomplete CPTS && MSP && SOS Médecins
    const currentAutocompleteValue = ref('');
    const autocompleteListArray = ref([]);

    async function fetchAutocomplete(e, type) {
      const { keyCode } = e;
      autocompleteListArray.value = [];
      checkAutocompleteValue(currentAutocompleteValue.value);
      if (
        keyCode !== 38
        && keyCode !== 40
        && keyCode !== 37
        && keyCode !== 39
        && e.target.value.length
      ) {
        const res = await DashboardService.getAutocompleteList(
          e.target.value,
          type,
        );
        autocompleteListArray.value = res;
      }
      showAutocompleteList.value = true;
      emit('get-autocomplete-val', {
        autocompleteValue: currentAutocompleteValue.value,
        autocompleteCheck: autocompleteCheck.value,
      });
    }

    function handleFocusOut() {
      showAutocompleteList.value = false;
      if (
        !currentAutocompleteValue.value
        || !currentAutocompleteValue.value.length
      ) {
        autocompleteListArray.value = [];
      }
    }

    // Autocomplete input validation
    const { autocompleteValueCheck } = useDashboard();
    const autocompleteCheck = ref({});
    function checkAutocompleteValue(inputVal) {
      autocompleteCheck.value = autocompleteValueCheck(
        props.sasParticipationVia,
        inputVal,
      );
    }

    function searchText(val) {
      currentAutocompleteValue.value = `${val.title} (${val.id_structure})`;
      checkAutocompleteValue(currentAutocompleteValue.value);
      autocompleteListArray.value = [];
      showAutocompleteList.value = false;
      emit('get-autocomplete-val', {
        autocompleteValue: currentAutocompleteValue.value,
        autocompleteCheck: autocompleteCheck.value,
      });
    }

    watch(
      () => props.autocompleteValue,
      () => {
        currentAutocompleteValue.value = props.autocompleteValue;
      },
    );

    // fetch label of autocomplete and refresh check value to parent component
    watch(
      () => props.label,
      () => {
        currentAutocompleteValue.value = props.label;
        checkAutocompleteValue(currentAutocompleteValue.value);
        emit('get-autocomplete-val', {
          autocompleteValue: currentAutocompleteValue.value,
          autocompleteCheck: autocompleteCheck.value,
        });
      },
    );

    watch(
      () => props.sasParticipationVia,
      () => {
        currentAutocompleteValue.value = '';
        checkAutocompleteValue(currentAutocompleteValue.value);
      },
    );

    onMounted(() => {
      checkAutocompleteValue(currentAutocompleteValue.value);
    });

    const autocompleteType = computed(() => {
      switch (props.sasParticipationVia) {
        case 2:
          return {
            name: 'cpts',
            label: 'Indiquez le nom de votre CPTS',
          };
        case 3:
          return {
            name: 'msp',
            label: 'Indiquez le nom de votre MSP',
          };
        case 4:
          return {
            name: 'sos',
            label: 'Indiquez le nom de votre association SOS Médecins',
          };
        default:
          return {
            name: '',
            label: '',
          };
      }
    });

    return {
      showAutocompleteList,
      currentAutocompleteValue,
      autocompleteListArray,
      fetchAutocomplete,
      autocompleteCheck,
      autocompleteType,
      searchText,
      handleFocusOut,
    };
  },
};
</script>
