<template>
  <fieldset class="search-retail__auto-apply">
    <legend class="sr-only">Les filtres de la recherche</legend>

    <template v-for="radio in radioInputConfig">
      <div
        v-if="radio.isVisible"
        :key="`search-radio-input-${radio.id}`"
        class="radio-standard search-retail__auto-apply__elem"
      >
        <input
          :type="radio.type"
          :id="radio.id"
          :name="radio.name"
          :value="radio.id"
          v-model="filterRadioValue"
          @change="onFilterChange"
        />
        <label :for="radio.id">{{ radio.label }}</label>
      </div>
    </template>
  </fieldset>
</template>

<script>
import { ref, watch } from 'vue';

export default {
  props: {
    radioInputConfig: {
      type: Array,
      default: () => ([]),
    },
    defaultValue: {
      type: String,
      default: '',
    },
  },
  emits: ['on-filter-change'],
  setup(props, { emit }) {
    const filterRadioValue = ref(props.defaultValue);

    function onFilterChange() {
      emit('on-filter-change', filterRadioValue.value);
    }

    watch(
      () => props.defaultValue,
      () => { filterRadioValue.value = props.defaultValue; },
    );

    return {
      filterRadioValue,
      onFilterChange,
    };
  },
};
</script>
